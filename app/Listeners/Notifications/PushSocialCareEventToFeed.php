<?php

namespace App\Listeners\Notifications;

use App\Events\SocialCare\CareOrderStatusChanged;
use App\Events\SocialCare\VisitReportSubmitted;
use App\Services\Notifications\NotificationFeedService;

class PushSocialCareEventToFeed
{
    public function __construct(
        protected NotificationFeedService $feed
    ) {}

    public function handle(CareOrderStatusChanged|VisitReportSubmitted $event): void
    {
        $order = $event->order ?? $event->details->order ?? null;

        if (! $order || ! $order->relationLoaded('user')) {
            $order?->loadMissing('user');
        }

        $user = $order?->user;

        if (! $user) {
            return;
        }

        if ($event instanceof CareOrderStatusChanged) {
            $title = 'Статус соцвизита обновлён';
            $body = sprintf(
                'Новый статус: %s',
                $event->newStatus->label()
            );

            $this->feed->push(
                $user,
                'social_care.status_changed',
                'social_care',
                $title,
                $body,
                $event->details,
                [
                    'order_id' => $order->id,
                    'care_order_details_id' => $event->details->id,
                    'status' => $event->newStatus->value,
                ]
            );
        }

        if ($event instanceof VisitReportSubmitted) {
            $details = $event->details;
            $title = 'Новый отчёт по визиту';
            $body = $details->careService
                ? "Отчёт по услуге: {$details->careService->name}"
                : 'Помощник оставил отчёт по визиту.';

            $this->feed->push(
                $user,
                'social_care.visit_report_created',
                'social_care',
                $title,
                $body,
                $details,
                [
                    'order_id' => $order->id,
                    'care_order_details_id' => $details->id,
                ]
            );
        }
    }
}
