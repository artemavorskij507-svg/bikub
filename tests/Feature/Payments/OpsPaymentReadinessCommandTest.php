<?php

namespace Tests\Feature\Payments;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpsPaymentReadinessCommandTest extends TestCase
{
    public function test_payment_readiness_command_outputs_warn_report_when_vipps_missing(): void
    {
        config()->set('services.vipps.client_id', null);
        config()->set('services.vipps.client_secret', null);
        config()->set('services.vipps.subscription_key', null);
        config()->set('services.vipps.merchant_serial_number', null);

        config()->set('services.stripe.key', 'pk_test_123');
        config()->set('services.stripe.secret', 'sk_test_123');
        config()->set('services.stripe.webhook.secret', 'whsec_123');

        Http::fake([
            'https://127.0.0.1/api/v1/payments/vipps/init' => Http::response([
                'success' => false,
                'message' => 'Vipps is not configured',
            ], 503),
            'https://127.0.0.1/api/v1/payments/vipps/shipping-details' => Http::response([
                'success' => true,
            ], 200),
            'https://127.0.0.1/api/v1/payments/vipps/consent-removal' => Http::response([
                'success' => true,
            ], 200),
            'https://127.0.0.1/api/v1/health' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $reportPath = storage_path('app/test-ops-payment-readiness-report.json');
        if (File::exists($reportPath)) {
            File::delete($reportPath);
        }

        $exitCode = Artisan::call('ops:payment-readiness', [
            '--insecure' => true,
            '--json' => $reportPath,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(File::exists($reportPath));

        $report = json_decode((string) File::get($reportPath), true);
        $this->assertIsArray($report);
        $this->assertSame('warn', $report['status'] ?? null);
        $this->assertSame(false, $report['providers']['vipps']['configured'] ?? true);
        $this->assertContains('VIPPS_CLIENT_ID', $report['providers']['vipps']['missing_keys'] ?? []);
        $this->assertSame(true, $report['providers']['stripe']['configured'] ?? false);
    }
}
