<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentSettingResource\Pages;
use App\Models\PaymentSetting;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class PaymentSettingResource extends Resource
{
    protected static ?string $model = PaymentSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Настройки платежей';

    protected static ?string $navigationGroup = 'Финансы';

    protected static ?int $navigationSort = 9;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Системные настройки';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('gateway')
                            ->label('Payment Gateway')
                            ->default('stripe')
                            ->required()
                            ->disabled()
                            ->helperText('Currently only Stripe is supported'),
                        Forms\Components\TextInput::make('label')
                            ->label('Configuration Name')
                            ->required()
                            ->placeholder('e.g., Stripe Production')
                            ->helperText('A friendly name for this configuration'),
                    ])
                    ->columns(2),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('publishable_key')
                            ->label('Publishable Key')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Your Stripe publishable key (starts with pk_)'),
                        Forms\Components\TextInput::make('secret_key')
                            ->label('Secret Key')
                            ->required()
                            ->password()
                            ->columnSpanFull()
                            ->helperText('Your Stripe secret key (starts with sk_)'),
                        Forms\Components\TextInput::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->helperText('Optional: Webhook endpoint secret for verification'),
                    ])
                    ->columns(1),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('currency')
                            ->label('Currency')
                            ->default('NOK')
                            ->required()
                            ->maxLength(3)
                            ->helperText('3-letter currency code (e.g., NOK, USD, EUR)'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable this payment configuration'),
                        Forms\Components\Toggle::make('is_test_mode')
                            ->label('Test Mode')
                            ->default(true)
                            ->helperText('Use test keys instead of live keys'),
                    ])
                    ->columns(3),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Textarea::make('additional_config')
                            ->label('Additional Configuration')
                            ->rows(3)
                            ->helperText('JSON configuration for additional settings'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('gateway')
                    ->label('Gateway')
                    ->colors([
                        'success' => ['stripe'],
                        'secondary',
                    ]),
                Tables\Columns\TextColumn::make('label')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('publishable_key')
                    ->label('Publishable Key')
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 20 ? $state : null;
                    }),
                Tables\Columns\BadgeColumn::make('currency')
                    ->label('Currency')
                    ->colors(['info']),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_test_mode')
                    ->label('Test Mode')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All configurations')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\TernaryFilter::make('is_test_mode')
                    ->label('Test Mode')
                    ->placeholder('All modes')
                    ->trueLabel('Test mode only')
                    ->falseLabel('Live mode only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (PaymentSetting $record) => $record->is_active ? 'Деактивировать' : 'Активировать')
                    ->icon(fn (PaymentSetting $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (PaymentSetting $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (PaymentSetting $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->is_active ? 'Настройка активирована' : 'Настройка деактивирована')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('test_connection')
                    ->label('Тест подключения')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->action(function (PaymentSetting $record) {
                        // Здесь можно добавить тест подключения к Stripe
                        \Filament\Notifications\Notification::make()
                            ->title('Тест подключения')
                            ->body('Функция тестирования подключения будет добавлена позже')
                            ->info()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'payment_settings_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Gateway', 'Label', 'Currency', 'Active', 'Test Mode', 'Updated']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->gateway,
                                    $record->label,
                                    $record->currency,
                                    $record->is_active ? 'Да' : 'Нет',
                                    $record->is_test_mode ? 'Да' : 'Нет',
                                    $record->updated_at->format('Y-m-d H:i:s'),
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
                            ->title('Настройки активированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListPaymentSettings::route('/'),
            'create' => Pages\CreatePaymentSetting::route('/create'),
            'edit' => Pages\EditPaymentSetting::route('/{record}/edit'),
        ];
    }
}
