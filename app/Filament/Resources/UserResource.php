<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $navigationGroup = 'Люди';

    protected static ?int $navigationSort = 106;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Профиль')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(32),
                        Forms\Components\TextInput::make('timezone')
                            ->label('Часовой пояс')
                            ->default('Europe/Oslo')
                            ->maxLength(64),
                        Forms\Components\Select::make('locale')
                            ->label('Язык интерфейса')
                            ->options([
                                'ru' => 'Русский',
                                'uk' => 'Українська',
                                'en' => 'English',
                            ])
                            ->default('ru')
                            ->searchable(),
                        Forms\Components\Toggle::make('marketing_opt_in')
                            ->label('Получать маркетинговые уведомления'),
                    ]),

                Forms\Components\Section::make('Безопасность')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(static fn ($state): bool => filled($state))
                            ->rule('min:8')
                            ->helperText(fn (string $context): string => $context === 'create'
                                ? 'Минимум 8 символов. Пароль будет захеширован автоматически.'
                                : 'Оставьте поле пустым, если не хотите менять пароль.'),
                        Forms\Components\Toggle::make('two_factor_enabled')
                            ->label('Двухфакторная аутентификация')
                            ->disabled()
                            ->helperText('Вкл/выкл через профиль безопасности пользователя.'),
                    ]),

                Forms\Components\Section::make('Статус и роли')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\Toggle::make('suspended')
                            ->label('Заблокирован')
                            ->helperText('Техническое поле: основано на suspended_at.')
                            ->dehydrated(false)
                            ->reactive()
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, ?User $record): void {
                                $component->state(! is_null($record?->suspended_at));
                            }),
                        Forms\Components\Select::make('roles')
                            ->label('Роли')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Статистика и активность')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('orders_stats')
                            ->label('Заказы')
                            ->content(fn (?User $record): string => $record
                                ? sprintf('%d заказов, последний: %s',
                                    (int) ($record->orders_count ?? 0),
                                    $record->last_order_at?->format('d.m.Y H:i') ?? '—',
                                )
                                : '—'),
                        Forms\Components\Placeholder::make('ltv_aov')
                            ->label('LTV / AOV')
                            ->content(function (?User $record): string {
                                if (! $record) {
                                    return '—';
                                }

                                $ltv = $record->ltv_cents
                                    ? number_format($record->ltv_cents / 100, 2, ',', ' ').' kr'
                                    : 'N/A';
                                $aov = $record->aov_cents
                                    ? number_format($record->aov_cents / 100, 2, ',', ' ').' kr'
                                    : 'N/A';

                                return "LTV: {$ltv} / AOV: {$aov}";
                            }),
                        Forms\Components\Placeholder::make('risk_level_display')
                            ->label('Риск')
                            ->content(fn (?User $record): string => $record?->risk_level ?? '—'),
                        Forms\Components\Placeholder::make('last_login_display')
                            ->label('Последний вход')
                            ->content(fn (?User $record): string => $record?->last_login_at?->format('d.m.Y H:i') ?? '—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->circular()
                    ->label(''),
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (User $record) => $record->email),
                BadgeColumn::make('roles.name')
                    ->label('Роли')
                    ->colors(['primary'])
                    ->limit(2),
                IconColumn::make('email_verified_at')
                    ->label('Email')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle'),
                IconColumn::make('phone_verified_at')
                    ->label('Тел.')
                    ->boolean(),
                TextColumn::make('orders_count')
                    ->label('Заказов')
                    ->sortable(),
                TextColumn::make('ltv_cents')
                    ->label('LTV')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, ',', ' ').' kr' : 'N/A')
                    ->sortable(),
                TextColumn::make('aov_cents')
                    ->label('AOV')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, ',', ' ').' kr' : 'N/A'),
                BadgeColumn::make('risk_level')
                    ->label('Риск')
                    ->colors(['success' => 'low', 'warning' => 'medium', 'danger' => 'high'])
                    ->icons(['heroicon-o-shield-check' => 'low', 'heroicon-o-exclamation-circle' => 'medium', 'heroicon-o-x-circle' => 'high']),
                IconColumn::make('two_factor_enabled')
                    ->label('2FA')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Последний вход')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('two_factor_enabled')->label('2FA'),
                Tables\Filters\SelectFilter::make('risk_level')->options([
                    'low' => 'Low', 'medium' => 'Medium', 'high' => 'High',
                ]),
                Tables\Filters\Filter::make('active')->label('Активные')
                    ->query(fn (Builder $q) => $q->whereNull('suspended_at')),
                Tables\Filters\Filter::make('period')->form([
                    Components\DatePicker::make('from'),
                    Components\DatePicker::make('to'),
                ])->query(function (Builder $query, array $data) {
                    return $query
                        ->when($data['from'] ?? null, fn (Builder $q, $v) => $q->where('last_login_at', '>=', $v))
                        ->when($data['to'] ?? null, fn (Builder $q, $v) => $q->where('last_login_at', '<=', $v));
                }),
                Tables\Filters\Filter::make('orders')->form([
                    Components\TextInput::make('min')->numeric(),
                    Components\TextInput::make('max')->numeric(),
                ])->query(function (Builder $query, array $data) {
                    return $query
                        ->when($data['min'] ?? null, fn (Builder $q, $v) => $q->where('orders_count', '>=', $v))
                        ->when($data['max'] ?? null, fn (Builder $q, $v) => $q->where('orders_count', '<=', $v));
                }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('impersonate')->label('Impersonate')
                    ->icon('heroicon-o-user-circle')
                    ->visible(fn (User $record) => method_exists(auth()->user(), 'can') ? auth()->user()->can('impersonate', $record) : false)
                    ->action(fn (User $record) => app(\App\Services\Impersonate::class)->start($record)),
                Tables\Actions\Action::make('suspend')->label('Suspend')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(fn (User $record) => is_null($record->suspended_at))
                    ->action(fn (User $record) => $record->update(['suspended_at' => now()])),
                Tables\Actions\Action::make('unsuspend')->label('Unblock')
                    ->visible(fn (User $record) => ! is_null($record->suspended_at))
                    ->action(fn (User $record) => $record->update(['suspended_at' => null])),
                Tables\Actions\Action::make('resetMfa')->label('Reset MFA')
                    ->action(fn (User $record) => event(new \App\Events\User\TwoFactorResetRequested($record))),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assignRole')->label('Назначить роль')
                    ->form([Components\Select::make('role_id')->label('Роль')->options(Role::pluck('name', 'id'))->required()])
                    ->action(function ($records, $data) {
                        $roleId = $data['role_id'] ?? null;
                        if ($roleId) {
                            $records->each->roles()->sync([$roleId]);
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'users_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Имя', 'Email', 'Телефон', 'Роли', 'Заказов', 'LTV', 'AOV', 'Риск', '2FA', 'Последний вход', 'Активен']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->name,
                                    $record->email,
                                    $record->phone ?? '—',
                                    $record->roles->pluck('name')->join(', '),
                                    $record->orders_count ?? 0,
                                    $record->ltv_cents ? number_format($record->ltv_cents / 100, 2, ',', ' ').' kr' : 'N/A',
                                    $record->aov_cents ? number_format($record->aov_cents / 100, 2, ',', ' ').' kr' : 'N/A',
                                    $record->risk_level ?? '—',
                                    $record->two_factor_enabled ? 'Да' : 'Нет',
                                    $record->last_login_at ? $record->last_login_at->format('Y-m-d H:i:s') : '—',
                                    is_null($record->suspended_at) ? 'Да' : 'Нет',
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
                            $record->update(['suspended_at' => null, 'is_active' => true]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Пользователи активированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('suspend')
                    ->label('Заблокировать')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['suspended_at' => now()]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Пользователи заблокированы')
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
