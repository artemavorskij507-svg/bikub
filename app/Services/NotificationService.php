<?php

namespace App\Services;

use App\Models\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send notification to user.
     */
    public function sendNotification(
        User $user,
        string $templateCode,
        string $channel,
        array $variables = [],
        ?Order $order = null,
        string $locale = 'no'
    ): bool {
        // Check user preferences
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('channel', $channel)
            ->first();

        if (! $preference || ! $preference->enabled) {
            Log::info('Notification disabled for user', [
                'user_id' => $user->id,
                'channel' => $channel,
                'template' => $templateCode,
            ]);

            return false;
        }

        // Use user's preferred locale
        $userLocale = $preference->locale ?? $locale;

        // Get template
        $template = NotificationTemplate::getTemplate($templateCode, $channel, $userLocale);
        if (! $template) {
            Log::error('Template not found', [
                'code' => $templateCode,
                'channel' => $channel,
                'locale' => $userLocale,
            ]);

            return false;
        }

        // Validate variables
        if (! $template->validateVariables($variables)) {
            Log::error('Invalid variables for template', [
                'template' => $templateCode,
                'variables' => $variables,
            ]);

            return false;
        }

        // Create notification event
        $event = NotificationEvent::create([
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'channel' => $channel,
            'template_code' => $templateCode,
            'payload' => $variables,
            'status' => 'queued',
        ]);

        try {
            // Send based on channel
            $success = match ($channel) {
                'email' => $this->sendEmail($user, $template, $variables),
                'sms' => $this->sendSms($user, $template, $variables),
                'push' => $this->sendPush($user, $template, $variables),
                default => false,
            };

            if ($success) {
                $event->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } else {
                $event->update([
                    'status' => 'failed',
                    'error' => 'Failed to send notification',
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            $event->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            Log::error('Notification sending failed', [
                'user_id' => $user->id,
                'template' => $templateCode,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email notification.
     */
    private function sendEmail(User $user, NotificationTemplate $template, array $variables): bool
    {
        try {
            $rendered = $template->render($variables);

            Mail::raw($rendered['body'], function ($message) use ($user, $rendered) {
                $message->to($user->email)
                    ->subject($rendered['subject']);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send SMS notification.
     */
    private function sendSms(User $user, NotificationTemplate $template, array $variables): bool
    {
        try {
            $rendered = $template->render($variables);

            // This would integrate with SMS provider (Twilio, etc.)
            // For now, just log
            Log::info('SMS would be sent', [
                'to' => $user->phone,
                'message' => $rendered['body'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send push notification.
     */
    private function sendPush(User $user, NotificationTemplate $template, array $variables): bool
    {
        try {
            $rendered = $template->render($variables);

            // This would integrate with FCM
            // For now, just log
            Log::info('Push notification would be sent', [
                'user_id' => $user->id,
                'title' => $rendered['subject'],
                'body' => $rendered['body'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Push notification sending failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send order-related notifications.
     */
    public function sendOrderNotification(Order $order, string $event): void
    {
        $variables = [
            'customer_name' => $order->user->name,
            'order_number' => $order->order_number,
            'order_total' => number_format($order->total_amount, 0, ',', ' ').' NOK',
            'eta' => $order->scheduled_at ? $order->scheduled_at->format('H:i') : 'N/A',
        ];

        // Send to customer
        $this->sendNotification(
            $order->user,
            $event,
            'email',
            $variables,
            $order
        );

        // Send push if user has preferences
        $this->sendNotification(
            $order->user,
            $event,
            'push',
            $variables,
            $order
        );
    }

    /**
     * Send courier notifications.
     */
    public function sendCourierNotification(User $courier, string $event, array $variables = []): void
    {
        $this->sendNotification(
            $courier,
            $event,
            'push',
            $variables
        );
    }

    /**
     * Test notification template.
     */
    public function testTemplate(string $templateCode, string $channel, string $locale, array $variables): array
    {
        $template = NotificationTemplate::getTemplate($templateCode, $channel, $locale);

        if (! $template) {
            return [
                'success' => false,
                'error' => 'Template not found',
            ];
        }

        if (! $template->validateVariables($variables)) {
            return [
                'success' => false,
                'error' => 'Invalid variables',
            ];
        }

        $rendered = $template->render($variables);

        return [
            'success' => true,
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
        ];
    }
}
