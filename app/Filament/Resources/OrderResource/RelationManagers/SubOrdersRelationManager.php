<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\ServiceType;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class SubOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'subOrders';

    protected static ?string $title = 'Связанные подзаказы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_number')
                    ->label('Номер заказа')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждён',
                        'in_progress' => 'В процессе',
                        'completed' => 'Завершён',
                        'cancelled' => 'Отменён',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Номер заказа')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Тип услуги')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $state) {
                            return '—';
                        }
                        $serviceType = ServiceType::tryFrom($state);
                        if ($serviceType) {
                            return $serviceType->label();
                        }
                        // Fallback для старых записей
                        if ($record && isset($record->metadata['service_type'])) {
                            return match ($record->metadata['service_type']) {
                                'eco_disposal' => 'Эко-услуги',
                                'roadside_assistance' => 'Помощь на дороге',
                                'vehicle_tow' => 'Эвакуация',
                                default => $record->metadata['service_type'],
                            };
                        }

                        return $state;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_total')
                    ->label('Сумма')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2, ',', ' ').' NOK' : '—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'secondary' => 'pending',
                        'info' => 'confirmed',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Запланировано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждён',
                        'in_progress' => 'В процессе',
                        'completed' => 'Завершён',
                        'cancelled' => 'Отменён',
                    ]),
                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Тип услуги')
                    ->options([
                        ServiceType::SOCIAL_CARE_VISIT->value ?? 'social_care_visit' => 'Соц. визит',
                        ServiceType::ECO_DISPOSAL->value ?? 'eco_disposal' => 'Эко-услуги',
                        ServiceType::HANDYMAN_HOURLY->value ?? 'handyman_hourly' => 'Мастер на час',
                        ServiceType::ROAD_ASSIST->value ?? 'roadside_assistance' => 'Помощь на дороге',
                        ServiceType::VEHICLE_TOW->value ?? 'vehicle_tow' => 'Эвакуация',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($value) {
                            $q->where('service_type', $value)
                                ->orWhere('metadata->service_type', $value);
                        });
                    }),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Открыть')
                    ->url(function ($record) {
                        // Determine which resource to use based on order type
                        if ($record->isSocialCare()) {
                            return \App\Filament\Resources\SocialCareOrderResource::getUrl('view', ['record' => $record->id]);
                        }
                        if ($record->isEcoDisposal()) {
                            return \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $record->id]);
                        }

                        return \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $record->id]);
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
