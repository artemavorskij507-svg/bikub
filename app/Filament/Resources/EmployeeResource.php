<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasUltraProMaxFeatures;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    use HasUltraProMaxFeatures;

    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Исполнители';

    protected static ?string $navigationGroup = 'Ресурсы и планирование';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Базовая информация о сотруднике')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(),
                            ])
                            ->columnSpan(1),
                        Forms\Components\Select::make('partner_id')
                            ->label('Партнер')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('employee_number')
                            ->label('Номер сотрудника')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => Employee::generateEmployeeNumber())
                            ->helperText('Автоматически генерируется, если не указан')
                            ->columnSpan(1),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активен',
                                'inactive' => 'Неактивен',
                                'on_leave' => 'В отпуске',
                            ])
                            ->required()
                            ->default('active')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Личные данные')
                    ->description('Имя, контакты и позиция')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Фамилия')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\Select::make('position')
                            ->label('Должность')
                            ->options([
                                'courier' => 'Курьер',
                                'dispatcher' => 'Диспетчер',
                                'technician' => 'Техник',
                                'assistant_l1' => 'Ассистент L1',
                                'assistant_l2' => 'Ассистент L2',
                                'assistant_l3' => 'Ассистент L3',
                                'manager' => 'Менеджер',
                            ])
                            ->searchable()
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Проверки и статусы')
                    ->description('Верификация и проверки')
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицирован')
                            ->helperText('Сотрудник прошел верификацию')
                            ->default(false)
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('background_check')
                            ->label('Проверка биографии')
                            ->helperText('Проверка биографии пройдена')
                            ->default(false)
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('hire_date')
                            ->label('Дата найма')
                            ->displayFormat('d.m.Y')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Навыки и дополнительные данные')
                    ->description('Навыки и метаданные')
                    ->schema([
                        Forms\Components\KeyValue::make('skills')
                            ->label('Навыки')
                            ->keyLabel('Навык')
                            ->valueLabel('Уровень')
                            ->helperText('Список навыков сотрудника')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Метаданные')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->helperText('Дополнительные данные в формате ключ-значение')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->getStateUsing(fn ($record) => $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Партнер')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('position')
                    ->label('Должность')
                    ->searchable()
                    ->sortable()
                    ->colors([
                        'info' => 'courier',
                        'warning' => 'dispatcher',
                        'success' => 'technician',
                        'gray' => fn ($state) => ! in_array($state, ['courier', 'dispatcher', 'technician'], true),
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Активен',
                        'inactive' => 'Неактивен',
                        'on_leave' => 'В отпуске',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'on_leave',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('background_check')
                    ->label('Проверка')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('hire_date')
                    ->label('Дата найма')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активен',
                        'inactive' => 'Неактивен',
                        'on_leave' => 'В отпуске',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('position')
                    ->label('Должность')
                    ->options([
                        'courier' => 'Курьер',
                        'dispatcher' => 'Диспетчер',
                        'technician' => 'Техник',
                        'assistant_l1' => 'Ассистент L1',
                        'assistant_l2' => 'Ассистент L2',
                        'assistant_l3' => 'Ассистент L3',
                        'manager' => 'Менеджер',
                    ])
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Верифицирован')
                    ->placeholder('Все')
                    ->trueLabel('Верифицированные')
                    ->falseLabel('Не верифицированные'),
                Tables\Filters\TernaryFilter::make('background_check')
                    ->label('Проверка биографии')
                    ->placeholder('Все')
                    ->trueLabel('Пройдена')
                    ->falseLabel('Не пройдена'),
                Tables\Filters\SelectFilter::make('partner_id')
                    ->label('Партнер')
                    ->relationship('partner', 'name')
                    ->searchable(),
                Tables\Filters\Filter::make('hire_date')
                    ->form([
                        Forms\Components\DatePicker::make('hire_from')
                            ->label('Дата найма от'),
                        Forms\Components\DatePicker::make('hire_until')
                            ->label('Дата найма до'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['hire_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('hire_date', '>=', $date),
                            )
                            ->when(
                                $data['hire_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('hire_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Employee $record) => $record->status === 'active' ? 'Деактивировать' : 'Активировать')
                    ->icon(fn (Employee $record) => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Employee $record) => $record->status === 'active' ? 'warning' : 'success')
                    ->action(function (Employee $record) {
                        $record->update([
                            'status' => $record->status === 'active' ? 'inactive' : 'active',
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Статус обновлен')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                ...static::getEnhancedBulkActions(),
                Tables\Actions\BulkAction::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['status' => 'active']);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Сотрудники активированы')
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
                            $record->update(['status' => 'inactive']);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Сотрудники деактивированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
