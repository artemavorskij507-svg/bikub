<?php

namespace App\Filament\Resources\Moving;

use App\Filament\Resources\Moving\ExecutorProfileResource\Pages;
use App\Models\Moving\ExecutorProfile;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ExecutorProfileResource extends Resource
{
    protected static ?string $model = ExecutorProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Moving';

    protected static ?int $navigationSort = 501;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основна інформація')
                    ->description('Базові дані профілю виконавця для переїздів і ремонтів.')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Користувач')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Оберіть користувача, до якого буде прив’язаний цей профіль виконавця. Кожен користувач може мати лише один профіль виконавця.'),
                        Forms\Components\Select::make('vehicle_type')
                            ->label('Тип транспорту')
                            ->options([
                                'van' => 'Фургон',
                                'truck' => 'Вантажівка',
                                'with_lift' => 'З підйомником',
                            ])
                            ->nullable()
                            ->helperText('Тип транспорту, яким користується виконавець. Необов’язково.'),
                        Forms\Components\Textarea::make('skills')
                            ->label('Навички')
                            ->helperText('Введіть ключові навички через кому (наприклад: assembly, takelage, electronics).')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Фізичні можливості та страхування')
                    ->description('Максимальні параметри навантаження та страхове покриття.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_volume')
                                    ->label('Макс. об\'єм (м³)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.1)
                                    ->nullable()
                                    ->helperText('Максимальний корисний об’єм, який може перевезти виконавець.'),
                                Forms\Components\TextInput::make('max_weight')
                                    ->label('Макс. вага (кг)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(1)
                                    ->nullable()
                                    ->helperText('Максимальна вага вантажу, яку може транспортувати виконавець.'),
                            ]),
                        Forms\Components\TextInput::make('insurance_limit')
                            ->label('Ліміт страхування, NOK')
                            ->numeric()
                            ->minValue(0)
                            ->step(100)
                            ->nullable()
                            ->helperText('Максимальна страхова сума за майно клієнта (за потреби).'),
                    ]),
                Forms\Components\Section::make('Ліцензія та активність')
                    ->description('Дані про ліцензію, рейтинг та активність виконавця.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('license_number')
                                    ->label('Номер ліцензії')
                                    ->nullable()
                                    ->helperText('Офіційний номер ліцензії або дозволу, якщо потрібен.'),
                                Forms\Components\DatePicker::make('license_expires_at')
                                    ->label('Термін дії ліцензії')
                                    ->nullable()
                                    ->helperText('Дата закінчення дії ліцензії (можна залишити порожнім).'),
                            ]),
                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг ★')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.01)
                            ->helperText('Середній рейтинг виконавця за відгуками клієнтів.'),
                        Forms\Components\TextInput::make('completed_orders_count')
                            ->label('Виконаних замовлень')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Сервісне поле, оновлюється автоматично на основі замовлень.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активний')
                            ->default(true)
                            ->helperText('Вимкніть, якщо тимчасово не хочете призначати цього виконавця.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Користувач')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vehicle_type')
                    ->label('Тип транспорту'),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),
                Tables\Columns\TextColumn::make('kpi.completed_orders')
                    ->label('Выполнено')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kpi.on_time_rate')
                    ->label('On-time %')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).'%' : '—')->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kpi.avg_rating')
                    ->label('Avg ★')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kpi.claims_count')
                    ->label('Претензий')->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kpi.quality_score')
                    ->label('Q-score')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->label('Тип транспорту')
                    ->options([
                        'van' => 'Фургон',
                        'truck' => 'Вантажівка',
                        'with_lift' => 'З підйомником',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активний')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки активні')
                    ->falseLabel('Тільки неактивні'),
                Tables\Filters\Filter::make('quality_issues')->label('Проблемні KPI')
                    ->query(fn (Builder $query) => $query->whereHas('kpi', function ($kpiQuery) {
                        $kpiQuery->where('quality_score', '<', 80)->orWhere('claims_count', '>', 0);
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListExecutorProfiles::route('/'),
            'create' => Pages\CreateExecutorProfile::route('/create'),
            'view' => Pages\ViewExecutorProfile::route('/{record}'),
            'edit' => Pages\EditExecutorProfile::route('/{record}/edit'),
        ];
    }
}
