<?php

namespace App\Filament\Widgets;

use App\Models\SocialCareEmergencyEvent;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SocialCareEmergencyWidget extends BaseWidget
{
    protected static ?string $heading = 'Экстренные сигналы';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '10s';

    protected function getTableQuery(): Builder
    {
        return SocialCareEmergencyEvent::query()
            ->with(['order', 'helperProfile.user', 'clientProfile', 'triggeredBy', 'handledBy'])
            ->whereNull('handled_at')
            ->orderByDesc('created_at');
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('level')
                    ->label('Уровень')
                    ->colors([
                        'info' => 'INFO',
                        'warning' => 'WARNING',
                        'danger' => 'CRITICAL',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'INFO' => 'Информация',
                        'WARNING' => 'Предупреждение',
                        'CRITICAL' => 'КРИТИЧЕСКОЕ',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('clientProfile.full_name')
                    ->label('Клиент')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('helperProfile.display_name')
                    ->label('Помощник')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('message')
                    ->label('Сообщение')
                    ->limit(50)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('source')
                    ->label('Источник')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'HELPER_APP' => 'Приложение помощника',
                        'CLIENT_APP' => 'Приложение клиента',
                        'COORDINATOR' => 'Координатор',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('isHandled')
                    ->label('Обработано')
                    ->getStateUsing(fn ($record) => $record->isHandled())
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('handle')
                    ->label('Взять в работу')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (SocialCareEmergencyEvent $record) {
                        $record->update([
                            'handled_by_user_id' => auth()->id(),
                            'handled_at' => now(),
                        ]);
                        $this->notify('success', 'Экстренный сигнал взят в работу');
                    })
                    ->visible(fn (SocialCareEmergencyEvent $record) => ! $record->isHandled()),

                Tables\Actions\Action::make('openOrder')
                    ->label('Открыть заказ')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (SocialCareEmergencyEvent $record) => $record->order
                            ? \App\Filament\Resources\SocialCareOrderResource::getUrl('view', ['record' => $record->order->id])
                            : null
                    )
                    ->visible(fn (SocialCareEmergencyEvent $record) => $record->order),

                Tables\Actions\Action::make('openClient')
                    ->label('Открыть клиента')
                    ->icon('heroicon-o-user')
                    ->url(fn (SocialCareEmergencyEvent $record) => $record->clientProfile
                            ? \App\Filament\Resources\ClientProfileResource::getUrl('view', ['record' => $record->clientProfile->id])
                            : null
                    )
                    ->visible(fn (SocialCareEmergencyEvent $record) => $record->clientProfile),
            ])
            ->heading(static::$heading);
    }
}
