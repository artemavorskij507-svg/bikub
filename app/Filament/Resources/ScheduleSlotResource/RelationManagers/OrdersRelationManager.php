<?php

namespace App\Filament\Resources\ScheduleSlotResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns as C;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Заказы';

    public static function table(Table $table): Table
    {
        return $table->columns([
            C\TextColumn::make('code')->label('Номер')->searchable(),
            C\BadgeColumn::make('pivot.reservation_status')->label('Резерв')->colors(['warning' => 'hold', 'success' => 'confirmed']),
            C\TextColumn::make('status')->label('Статус'),
            C\TextColumn::make('customer.full_name')->label('Клиент')->toggleable(),
            C\TextColumn::make('total_amount')->money('nok'),
        ])->actions([
            Tables\Actions\Action::make('confirm')->label('Подтвердить')->visible(fn ($r) => $r->pivot->reservation_status === 'hold')
                ->action(function ($record, $livewire) {
                    $slot = $this->getOwnerRecord();
                    app(\App\Services\Scheduling\SlotPlanner::class)->confirm($slot, $record);
                    $livewire->notify('success', 'Подтверждено');
                }),
            Tables\Actions\Action::make('release')->label('Снять')
                ->action(function ($record, $livewire) {
                    app(\App\Services\Scheduling\SlotPlanner::class)->release($this->getOwnerRecord(), $record);
                    $livewire->notify('success', 'Снято');
                }),
        ]);
    }
}
