<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\ServiceType;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderLinkingService;
use Filament\Forms;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getActions(): array
    {
        $order = $this->record;

        return [
            // Create Handyman sub-order для доставки
            Actions\Action::make('createHandymanSubOrder')
                ->label('Создать заказ мастера')
                ->icon('heroicon-o-wrench')
                ->color('primary')
                ->visible(fn () => in_array($order->service_type, [
                    ServiceType::GROCERY_DELIVERY->value,
                    // TODO: добавить ServiceType::DELIVERY_BULKY, DELIVERY_APPLIANCE если есть
                ]))
                ->form([
                    Forms\Components\Select::make('handyman_service_type')
                        ->label('Тип услуги мастера')
                        ->options([
                            ServiceType::HANDYMAN_FIXED->value => 'Фиксированная услуга',
                            ServiceType::HANDYMAN_HOURLY->value => 'Почасовая работа',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('estimated_total')
                        ->label('Оценочная стоимость (в минимальных единицах)')
                        ->numeric()
                        ->nullable(),
                ])
                ->action(function (array $data) use ($order) {
                    $linker = app(OrderLinkingService::class);
                    $subOrder = $linker->createHandymanSubOrder(
                        $order,
                        $data['handyman_service_type'],
                        [
                            'estimated_total' => $data['estimated_total'] ?? null,
                        ],
                    );
                    $this->notify('success', "Создан подзаказ мастера #{$subOrder->order_number}");
                }),
            // Create Eco sub-order для ремонта/переезда
            Actions\Action::make('createEcoSubOrder')
                ->label('Создать ЭКО-заказ (утилизация)')
                ->icon('heroicon-o-trash')
                ->color('success')
                ->visible(fn () => in_array($order->service_type, [
                    ServiceType::COMPLEX_REPAIR->value,
                    // TODO: добавить ServiceType::RELOCATION если есть
                ]))
                ->requiresConfirmation()
                ->action(function () use ($order) {
                    $linker = app(OrderLinkingService::class);
                    $subOrder = $linker->createEcoSubOrder($order);
                    $this->notify('success', "Создан ЭКО-подзаказ #{$subOrder->order_number}");
                }),
            // Create Cleaning sub-order для ремонта/переезда
            Actions\Action::make('createCleaningSubOrder')
                ->label('Создать заказ на уборку')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->visible(fn () => in_array($order->service_type, [
                    ServiceType::COMPLEX_REPAIR->value,
                    // TODO: добавить ServiceType::RELOCATION если есть
                ]))
                ->requiresConfirmation()
                ->action(function () use ($order) {
                    $linker = app(OrderLinkingService::class);
                    $subOrder = $linker->createCleaningSubOrder($order);
                    $this->notify('success', "Создан подзаказ на уборку #{$subOrder->order_number}");
                }),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Загружаем roadsideDetails для отображения в форме
        $order = $this->record;
        if ($order && $order->isRoadside()) {
            $order->load('roadsideDetails.partner');
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $order = $this->record;

        // Если это roadside-заказ
        if ($order && $order->isRoadside()) {
            // Обновляем roadside_partner_id в Order
            if (isset($data['roadside_partner_id'])) {
                // Якщо є roadsideDetails, також оновлюємо partner_id там
                $roadsideDetails = $order->roadsideDetails;
                if ($roadsideDetails && $data['roadside_partner_id']) {
                    $roadsideDetails->partner_id = $data['roadside_partner_id'];
                    $roadsideDetails->save();
                } elseif ($data['roadside_partner_id'] && ! $roadsideDetails) {
                    // Если roadsideDetails не существует, создаём его
                    \App\Models\RoadsideAssistanceDetail::create([
                        'order_id' => $order->id,
                        'partner_id' => $data['roadside_partner_id'],
                    ]);
                }
            }

            // Если статус был 'pending' и появился assigned_to или roadside_partner_id - меняем на 'assigned'
            $originalStatus = $order->getOriginal('status') ?? $order->status;
            $newAssignedTo = $data['assigned_to'] ?? null;
            $newPartnerId = $data['roadside_partner_id'] ?? null;

            if ($originalStatus === 'pending') {
                if ($newAssignedTo || $newPartnerId) {
                    $data['status'] = 'assigned';
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Оновлюємо roadsideDetails, якщо змінився roadside_partner_id
        $order = $this->record;
        if ($order->isRoadside() && $order->roadside_partner_id) {
            $roadsideDetails = $order->roadsideDetails;
            if ($roadsideDetails && $roadsideDetails->partner_id !== $order->roadside_partner_id) {
                $roadsideDetails->partner_id = $order->roadside_partner_id;
                $roadsideDetails->save();
            }
        }
    }
}
