<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareServiceResource\Pages;
use App\Models\CareService;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class CareServiceResource extends Resource
{
    protected static ?string $model = CareService::class;

    protected static ?string $navigationGroup = 'Social Care';

    protected static ?int $navigationSort = 707;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $navigationLabel = 'Типы услуг';

    protected static ?string $modelLabel = 'Услуга заботы';

    protected static ?string $pluralModelLabel = 'Услуги заботы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основное')
                    ->description('Название и код услуги, которая будет использоваться в планах заботы и визитах.')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Код')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('например: daily_visit, medicine_check')
                            ->helperText('Технический код (slug) без пробелов. Используется в интеграциях и логике.'),
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: Ежедневный визит, Контроль приёма лекарств')
                            ->helperText('Человеко‑понятное название услуги, которое видит координатор и помощники.'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->placeholder('Кратко опишите, что делает помощник во время визита, какие задачи входят и что важно учитывать.')
                            ->helperText('Необязательное поле, но помогает координатору выбирать правильную услугу.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Параметры услуги')
                    ->description('Минимальные требования к помощнику, длительность визита и базовая стоимость.')
                    ->schema([
                        Forms\Components\Select::make('required_level')
                            ->label('Требуемый уровень помощника')
                            ->options([
                                'SOCIAL_HELPER' => 'Social Helper',
                                'COMMUNITY_PARTNER' => 'Community Partner',
                                'BIKUBE_FRIEND' => 'Bikube Friend',
                            ])
                            ->required()
                            ->helperText('Какой минимальный уровень профиля должен иметь помощник, чтобы выполнять эту услугу.'),
                        Forms\Components\TextInput::make('base_duration_minutes')
                            ->label('Базовая длительность (минуты)')
                            ->numeric()
                            ->minValue(15)
                            ->step(15)
                            ->required()
                            ->default(60)
                            ->helperText('Стандартное время визита. Можно указывать кратно 15 минут.'),
                        Forms\Components\TextInput::make('base_price_nok')
                            ->label('Базовая цена (NOK)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->placeholder('например: 490')
                            ->helperText('Необязательно. Если пусто, цена может рассчитываться динамически по тарифам.'),
                        Forms\Components\Toggle::make('is_recurring_available')
                            ->label('Доступна как подписка')
                            ->default(false)
                            ->helperText('Если включено, услугу можно добавлять в регулярные планы (еженедельно, ежемесячно и т.п.).'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->helperText('Неактивные услуги скрываются из выбора, но остаются в истории планов и визитов.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('required_level')
                    ->label('Уровень'),
                Tables\Columns\TextColumn::make('base_duration_minutes')
                    ->label('Длительность (мин)')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((int) $state, 0, ',', ' ') : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_price_nok')
                    ->label('Цена (NOK)')
                    ->money('NOK')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_recurring_available')
                    ->label('Подписка')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                Tables\Columns\TextColumn::make('care_plans_count')
                    ->label('Активных планов')
                    ->counts('carePlans')
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('care_orders_count')
                    ->label('Заказов')
                    ->counts('careOrders')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('required_level')
                    ->label('Уровень')
                    ->multiple()
                    ->options([
                        'SOCIAL_HELPER' => 'Social Helper',
                        'COMMUNITY_PARTNER' => 'Community Partner',
                        'BIKUBE_FRIEND' => 'Bikube Friend',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),
                Tables\Filters\TernaryFilter::make('is_recurring_available')
                    ->label('Доступна как подписка'),
                Tables\Filters\Filter::make('has_plans')
                    ->label('С активными планами')
                    ->query(fn (Builder $query): Builder => $query->whereHas('carePlans', function ($q) {
                        $q->where('status', 'ACTIVE');
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (CareService $record) => $record->is_active ? 'Деактивировать' : 'Активировать')
                    ->icon(fn (CareService $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (CareService $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (CareService $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->is_active ? 'Услуга активирована' : 'Услуга деактивирована')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'care_services_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Код', 'Название', 'Уровень', 'Длительность (мин)', 'Цена (NOK)', 'Подписка', 'Активна', 'Планов', 'Заказов']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->code,
                                    $record->name,
                                    $record->required_level,
                                    $record->base_duration_minutes,
                                    $record->base_price_nok ?? '—',
                                    $record->is_recurring_available ? 'Да' : 'Нет',
                                    $record->is_active ? 'Да' : 'Нет',
                                    $record->carePlans()->count(),
                                    $record->careOrders()->count(),
                                ]);
                            }
                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
                Tables\Actions\BulkAction::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => true]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Услуги активированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Деактивировать')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => false]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Услуги деактивированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCareServices::route('/'),
            'create' => Pages\CreateCareService::route('/create'),
            'view' => Pages\ViewCareService::route('/{record}'),
            'edit' => Pages\EditCareService::route('/{record}/edit'),
        ];
    }
}
