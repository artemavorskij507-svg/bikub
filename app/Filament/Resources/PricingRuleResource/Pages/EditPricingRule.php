<?php

namespace App\Filament\Resources\PricingRuleResource\Pages;

use App\Filament\Resources\PricingRuleResource;
use App\Services\Pricing\OrderContext;
use App\Services\Pricing\PriceEngine;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPricingRule extends EditRecord
{
    protected static string $resource = PricingRuleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('previewEstimate')
                ->label('Проверить расценку')
                ->icon('heroicon-o-calculator')
                ->form([
                    Forms\Components\TextInput::make('service_type')
                        ->label('Service Type')
                        ->default(fn () => $this->record?->service_type ?? $this->record?->serviceType?->slug)
                        ->required(),
                    Forms\Components\TextInput::make('zone')
                        ->label('Zone')
                        ->placeholder('Narvik sentrum'),
                    Forms\Components\TextInput::make('distance_km')
                        ->label('Distance (km)')
                        ->numeric()
                        ->default(5),
                    Forms\Components\TextInput::make('total_weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->default(1),
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Когда выполняется')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    $context = OrderContext::fromArray([
                        'service_type' => $data['service_type'],
                        'zone' => $data['zone'] ?? null,
                        'distance_km' => $data['distance_km'] ?? null,
                        'total_weight_kg' => $data['total_weight_kg'] ?? null,
                        'scheduled_at' => $data['scheduled_at'] ?? now()->toDateTimeString(),
                    ]);

                    $result = app(PriceEngine::class)->estimate($context);

                    Notification::make()
                        ->title('Предварительный расчет')
                        ->body(sprintf(
                            "Итог: %s %s\nБазовая часть: %s\nПравил применено: %d",
                            number_format($result->total, 2),
                            $result->currency,
                            number_format($result->subtotal, 2),
                            count($result->breakdown)
                        ))
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
