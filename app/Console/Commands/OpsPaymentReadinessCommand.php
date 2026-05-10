<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class OpsPaymentReadinessCommand extends Command
{
    protected $signature = 'ops:payment-readiness
        {--json= : Optional JSON output path}
        {--base-url= : Probe base URL (default https://127.0.0.1)}
        {--insecure : Disable TLS verification for local/self-signed probes}';

    protected $description = 'Check payment provider readiness (Vipps/Stripe) with explicit config and endpoint probes';

    public function handle(): int
    {
        $baseUrl = rtrim((string) ($this->option('base-url') ?: 'https://127.0.0.1'), '/');
        $insecure = (bool) $this->option('insecure');

        $http = Http::acceptJson()
            ->withOptions(['allow_redirects' => false]);

        if ($insecure) {
            $http = $http->withoutVerifying();
        }

        $vippsConfig = [
            'VIPPS_CLIENT_ID' => config('services.vipps.client_id', env('VIPPS_CLIENT_ID')),
            'VIPPS_CLIENT_SECRET' => config('services.vipps.client_secret', env('VIPPS_CLIENT_SECRET')),
            'VIPPS_SUBSCRIPTION_KEY' => config('services.vipps.subscription_key', env('VIPPS_SUBSCRIPTION_KEY')),
            'VIPPS_MERCHANT_SERIAL_NUMBER' => config('services.vipps.merchant_serial_number', env('VIPPS_MERCHANT_SERIAL_NUMBER')),
        ];

        $stripeConfig = [
            'STRIPE_PUBLISHABLE_KEY' => config('services.stripe.key', env('STRIPE_PUBLISHABLE_KEY', env('STRIPE_KEY'))),
            'STRIPE_SECRET_KEY' => config('services.stripe.secret', env('STRIPE_SECRET_KEY', env('STRIPE_SECRET'))),
            'STRIPE_WEBHOOK_SECRET' => config('services.stripe.webhook.secret', env('STRIPE_WEBHOOK_SECRET')),
        ];

        $vippsMissing = collect($vippsConfig)
            ->filter(fn ($value): bool => blank($value))
            ->keys()
            ->values()
            ->all();

        $stripeMissing = collect($stripeConfig)
            ->filter(fn ($value): bool => blank($value))
            ->keys()
            ->values()
            ->all();

        $probes = [
            'vipps_init' => $this->probe($http, 'POST', $baseUrl.'/api/v1/payments/vipps/init', []),
            'vipps_shipping_details' => $this->probe($http, 'GET', $baseUrl.'/api/v1/payments/vipps/shipping-details'),
            'vipps_consent_removal' => $this->probe($http, 'POST', $baseUrl.'/api/v1/payments/vipps/consent-removal', []),
            'stripe_health' => $this->probe($http, 'GET', $baseUrl.'/api/v1/health'),
        ];

        $vippsReady = empty($vippsMissing);
        $stripeReady = empty($stripeMissing);

        $status = 'pass';
        if (! $vippsReady || ! $stripeReady) {
            $status = 'warn';
        }

        if ($probes['vipps_shipping_details']['status_code'] >= 500 || $probes['vipps_consent_removal']['status_code'] >= 500) {
            $status = 'fail';
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'status' => $status,
            'base_url' => $baseUrl,
            'tls_insecure' => $insecure,
            'providers' => [
                'vipps' => [
                    'configured' => $vippsReady,
                    'missing_keys' => $vippsMissing,
                ],
                'stripe' => [
                    'configured' => $stripeReady,
                    'missing_keys' => $stripeMissing,
                ],
            ],
            'probes' => $probes,
        ];

        $jsonPath = (string) ($this->option('json') ?: storage_path('app/ops-payment-readiness-report.json'));
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->line('Ops payment readiness: '.$status);
        $this->line('Report: '.$jsonPath);

        if ($status === 'pass') {
            return self::SUCCESS;
        }

        return $status === 'warn' ? self::SUCCESS : self::FAILURE;
    }

    private function probe($http, string $method, string $url, array $payload = []): array
    {
        try {
            $response = strtoupper($method) === 'POST'
                ? $http->post($url, $payload)
                : $http->get($url);

            $body = $response->json();
            if (! is_array($body)) {
                $body = ['raw' => mb_substr((string) $response->body(), 0, 300)];
            }

            return [
                'status_code' => $response->status(),
                'ok' => $response->ok(),
                'body' => $body,
            ];
        } catch (\Throwable $e) {
            return [
                'status_code' => 0,
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
