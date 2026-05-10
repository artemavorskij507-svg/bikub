<?php

namespace App\Filament\Resources;

use App\Models\Partner;
use Filament\Forms\Components as F;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters;
use Illuminate\Database\Eloquent\Builder;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-office-building';

    // TODO fixed by Cursor: normalize navigation group name to unified 'Ресурсы и планирование'
    protected static ?string $navigationGroup = 'Люди';

    protected static ?string $navigationLabel = 'Партнёры';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            F\Section::make('Основное')->schema([
                F\TextInput::make('name')->label('Название')->required()->maxLength(190),
                F\Select::make('type')->options([
                    'logistics' => 'Логистика', 'pharmacy' => 'Аптека', 'grocery' => 'Продукты',
                    'service_provider' => 'Сервис', 'auto_service' => 'Авто', 'recycling' => 'Ресайклинг',
                    'towing_service' => 'Эвакуатор / буксировка',
                    'roadside_mobile' => 'Мобильная помощь',
                    'repair_shop' => 'СТО / сервис',
                    'inspection_center' => 'Осмотр / диагностика',
                    'service_station' => 'СТО / сервис',
                    'autoservice' => 'Автосервис',
                    'inspection_expert' => 'Эксперт осмотра',
                    'other' => 'Другое',
                ])->required(),
                F\Select::make('geo_zone_id')
                    ->label('Геозона')
                    ->relationship('geoZone', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                F\TextInput::make('priority')
                    ->label('Приоритет')
                    ->numeric()
                    ->default(100)
                    ->helperText('Чем меньше — тем приоритетнее для автоназначения')
                    ->minValue(1)
                    ->maxValue(999),
                F\Toggle::make('active')->label('Активен')->inline(false)->default(true),
                F\TextInput::make('org_number')->label('Org.nr')->maxLength(32),
                F\TextInput::make('vat_number')->label('VAT')->maxLength(32),
                F\Toggle::make('vat_registered')->label('VAT registered')->default(true),
                F\TextInput::make('invoice_email')->email(),
                F\TextInput::make('support_email')->email(),
                F\TextInput::make('support_phone')->tel(),
                F\Textarea::make('notes')->columnSpanFull(),
            ])->columns(3),
            F\Section::make('SLA и KPI')->schema([
                F\TextInput::make('sla_target_min')->numeric()->minValue(10)->maxValue(480)->suffix('мин'),
                F\TextInput::make('on_time_rate')->numeric()->suffix('%')->rule('between:0,100'),
                F\TextInput::make('rating_avg')->numeric()->step('0.01')->minValue(0)->maxValue(5),
                F\TextInput::make('rating_count')->numeric()->minValue(0),
                F\DatePicker::make('contract_valid_to')->label('Договор до'),
            ])->columns(5),
            F\Section::make('Roadside-способности')
                ->schema([
                    F\CheckboxList::make('capabilities')
                        ->label('Возможности')
                        ->options([
                            'jump_start' => 'Прикуривание (Прикурить аккумулятор)',
                            'wheel_change' => 'Замена колеса (Замена проколотого колеса)',
                            'fuel_delivery' => 'Подвоз топлива (Доставка топлива)',
                            'towing' => 'Эвакуация (Эвакуация автомобиля)',
                            'winching' => 'Вытаскивание (Вытаскивание из грязи/снега)',
                            'diagnostics' => 'Диагностика / осмотр (Диагностика проблем)',
                        ])
                        ->columns(2)
                        ->visible(fn ($get) => in_array($get('type'), [
                            'towing_service',
                            'roadside_mobile',
                            'repair_shop',
                            'inspection_center',
                            'service_station',
                            'autoservice',
                            'inspection_expert',
                        ])),
                ])
                ->collapsible()
                ->collapsed()
                ->visible(fn ($get) => in_array($get('type'), [
                    'towing_service',
                    'roadside_mobile',
                    'repair_shop',
                    'inspection_center',
                    'service_station',
                    'autoservice',
                    'inspection_expert',
                ])),
            F\Section::make('Интеграция')->schema([
                F\TextInput::make('webhook_url')->url(),
                F\TextInput::make('api_key')->password(),
                F\KeyValue::make('payout_terms')->label('Payout terms')->keyLabel('Ключ')->valueLabel('Значение'),
                F\KeyValue::make('flags')->label('Фича-флаги')->keyLabel('Ключ')->valueLabel('Значение'),
            ])->columns(2)->collapsed(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                C\TextColumn::make('name')->label('Партнёр')->searchable()->sortable()->weight('semibold')
                    ->description(fn (Partner $r) => $r->org_number),
                C\BadgeColumn::make('type')->label('Тип')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'towing_service' => 'Эвакуатор',
                        'roadside_mobile' => 'Мобильная помощь',
                        'repair_shop' => 'СТО',
                        'inspection_center' => 'Осмотр',
                        'service_station' => 'СТО',
                        'autoservice' => 'Автосервис',
                        'inspection_expert' => 'Эксперт',
                        'logistics' => 'Логистика',
                        'pharmacy' => 'Аптека',
                        'grocery' => 'Продукты',
                        'service_provider' => 'Сервис',
                        'auto_service' => 'Авто',
                        'recycling' => 'Ресайклинг',
                        'other' => 'Другое',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'towing_service',
                        'warning' => ['service_station', 'repair_shop', 'roadside_mobile', 'logistics'],
                        'info' => ['inspection_center', 'inspection_expert', 'autoservice', 'grocery'],
                        'primary' => 'service_provider',
                        'success' => 'pharmacy',
                        'gray' => 'other',
                    ]),
                C\IconColumn::make('active')->label('Статус')->boolean(),
                C\TextColumn::make('geoZone.name')
                    ->label('Геозона')
                    ->searchable()
                    ->sortable()
                    ->default('—')
                    ->toggleable(),
                C\TextColumn::make('priority')
                    ->label('Приоритет')
                    ->sortable()
                    ->default(100)
                    ->toggleable()
                    ->visible(fn ($record) => $record && in_array($record->type, [
                        'towing_service',
                        'roadside_mobile',
                        'repair_shop',
                        'inspection_center',
                        'service_station',
                        'autoservice',
                        'inspection_expert',
                    ])),
                C\TextColumn::make('zones_count')->counts('zones')->label('Зоны')->sortable(),
                C\TextColumn::make('services_count')->counts('services')->label('Услуги')->sortable(),
                C\TextColumn::make('sla_target_min')->label('SLA таргет')->suffix(' мин'),
                C\BadgeColumn::make('on_time_rate')->label('On-time')
                    ->colors([
                        'danger' => fn ($state) => $state !== null && $state < 80,
                        'warning' => fn ($state) => $state !== null && $state >= 80 && $state < 95,
                        'success' => fn ($state) => $state !== null && $state >= 95,
                    ])
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2).' %' : 'N/A'),
                C\TextColumn::make('rating_avg')->label('★')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2) : 'N/A'),
                C\TextColumn::make('contract_valid_to')->label('Договор до')->date(),
                C\TextColumn::make('updated_at')->since()->label('Обновлён'),
            ])
            ->filters([
                Filters\SelectFilter::make('type')->label('Тип')->options([
                    'logistics' => 'Логистика', 'pharmacy' => 'Аптека', 'grocery' => 'Продукты',
                    'service_provider' => 'Сервис', 'auto_service' => 'Авто', 'recycling' => 'Ресайклинг',
                    'towing_service' => 'Эвакуатор / буксировка',
                    'roadside_mobile' => 'Мобильная помощь',
                    'repair_shop' => 'СТО / сервис',
                    'inspection_center' => 'Осмотр / диагностика',
                    'service_station' => 'СТО / сервис',
                    'autoservice' => 'Автосервис',
                    'inspection_expert' => 'Эксперт осмотра',
                    'other' => 'Другое',
                ]),
                Filters\TernaryFilter::make('active')->label('Активные'),
                Filters\Filter::make('expires')
                    ->label('Договор истекает ≤ 30 дней')
                    ->query(fn (Builder $q) => $q->whereNotNull('contract_valid_to')
                        ->where('contract_valid_to', '<=', now()->addDays(30))),
                Filters\SelectFilter::make('zone')->label('Зона')
                    ->relationship('zones', 'name')->multiple(),
                Filters\SelectFilter::make('service')->label('Услуга')
                    ->relationship('services', 'name')->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleActive')->label('Вкл/Выкл')
                    ->icon('heroicon-o-lightning-bolt')->color('secondary')
                    ->action(fn (Partner $r) => $r->update(['active' => ! $r->active])),
                Tables\Actions\Action::make('testWebhook')->label('Тест вебхука')
                    ->icon('heroicon-o-wifi')->requiresConfirmation()
                    ->action(fn (Partner $r) => \App\Jobs\Partners\TestWebhook::dispatch($r->id)),
                Tables\Actions\Action::make('renewContract')->label('Продлить 1 год')
                    ->icon('heroicon-o-document-text')
                    ->action(fn (Partner $r) => $r->update(['contract_valid_to' => ($r->contract_valid_to?->addYear() ?? now()->addYear())])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')->label('Активировать')
                    ->action(fn ($records) => $records->each->update(['active' => true])),
                Tables\Actions\BulkAction::make('deactivate')->label('Деактивировать')
                    ->color('danger')->action(fn ($records) => $records->each->update(['active' => false])),
                Tables\Actions\BulkAction::make('assignZones')->label('Назначить зоны')
                    ->form([F\Select::make('zones')->multiple()->relationship('zones', 'name')])
                    ->action(function ($records, $data) {
                        foreach ($records as $r) {
                            $r->zones()->syncWithoutDetaching($data['zones'] ?? []);
                        }
                    }),
                Tables\Actions\BulkAction::make('assignServices')->label('Назначить услуги')
                    ->form([F\Select::make('services')->multiple()->relationship('services', 'name')])
                    ->action(function ($records, $data) {
                        foreach ($records as $r) {
                            $r->services()->syncWithoutDetaching($data['services'] ?? []);
                        }
                    }),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PartnerResource\RelationManagers\ContactsRelationManager::class,
            PartnerResource\RelationManagers\ContractsRelationManager::class,
            PartnerResource\RelationManagers\ZonesRelationManager::class,
            PartnerResource\RelationManagers\ServicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => PartnerResource\Pages\ListPartners::route('/'),
            'create' => PartnerResource\Pages\CreatePartner::route('/create'),
            'view' => PartnerResource\Pages\ViewPartner::route('/{record}'),
            'edit' => PartnerResource\Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
