<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WebhookSignatureValidator
{
    /**
     * Verify webhook signature based on provider
     */
    public function verify(string $provider, string $rawPayload, \Illuminate\Http\Request $request): bool
    {
        return match ($provider) {
            'stripe' => $this->verifyStripe($rawPayload, $request),
            'n8n' => $this->verifyN8n($rawPayload, $request),
            default => true, // No verification for other providers (internal, sms, etc.)
        };
    }

    /**
     * Verify Stripe webhook signature
     * Using Stripe's official library approach (timestamp + signature)
     */
    private function verifyStripe(string $rawPayload, \Illuminate\Http\Request $request): bool
    {
        $secret = config('webhooks.providers.stripe.webhook_secret');
        if (! $secret) {
            Log::warning('Stripe webhook secret not configured');

            return false;
        }

        $signature = $request->header('Stripe-Signature');
        if (! $signature) {
            Log::warning('Stripe webhook missing signature header');

            return false;
        }

        // Parse Stripe signature header: t=timestamp,v1=signature
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[trim($key)] = trim($value);
        }

        if (! isset($parts['t']) || ! isset($parts['v1'])) {
            Log::warning('Stripe webhook signature malformed');

            return false;
        }

        $timestamp = $parts['t'];
        $providedSignature = $parts['v1'];

        // Check timestamp is recent (±300 seconds)
        if (abs(time() - (int) $timestamp) > 300) {
            Log::warning('Stripe webhook timestamp out of range', ['ts' => $timestamp]);

            return false;
        }

        // Compute expected signature: HMAC-SHA256(timestamp.payload, secret)
        $signedContent = "$timestamp.$rawPayload";
        $expectedSignature = hash_hmac('sha256', $signedContent, $secret);

        // Constant-time comparison to prevent timing attacks
        if (! hash_equals($expectedSignature, $providedSignature)) {
            Log::warning('Stripe webhook signature mismatch');

            return false;
        }

        return true;
    }

    /**
     * Verify n8n webhook signature
     * Using HMAC SHA256 with timestamp validation
     */
    private function verifyN8n(string $rawPayload, \Illuminate\Http\Request $request): bool
    {
        $secret = config('webhooks.providers.n8n.webhook_secret');
        if (! $secret) {
            Log::warning('n8n webhook secret not configured');

            return false;
        }

        $signature = $request->header('X-N8N-Signature');
        $timestamp = $request->header('X-N8N-Timestamp');

        if (! $signature || ! $timestamp) {
            Log::warning('n8n webhook missing signature or timestamp header');

            return false;
        }

        // Check timestamp is recent (±300 seconds)
        if (abs(time() - (int) $timestamp) > 300) {
            Log::warning('n8n webhook timestamp out of range', ['ts' => $timestamp]);

            return false;
        }

        // Compute expected signature: HMAC-SHA256(timestamp.payload, secret)
        $signedContent = "$timestamp.$rawPayload";
        $expectedSignature = hash_hmac('sha256', $signedContent, $secret);

        // Constant-time comparison
        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('n8n webhook signature mismatch');

            return false;
        }

        return true;
    }
}
