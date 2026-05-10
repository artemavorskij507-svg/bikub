<?php

namespace App\Filament\Widgets;

use App\Models\PaymentSetting;
use Filament\Widgets\Widget;

class PaymentStatus extends Widget
{
    protected static string $view = 'filament.widgets.payment-status';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'lg' => 1,
    ];

    public function getViewData(): array
    {
        $payment = PaymentSetting::first();

        return [
            'payment' => $payment,
            'isActive' => (bool) ($payment->is_active ?? false),
            'isTest' => (bool) ($payment->is_test_mode ?? true),
        ];
    }
}
