<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('owner_type')
                            ->label('Owner Type')
                            ->options([
                                'user' => 'User',
                                'partner' => 'Partner',
                                'service' => 'Service',
                            ])
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\TextInput::make('owner_id')
                            ->label('Owner ID')
                            ->numeric()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\TagsInput::make('scopes')
                            ->label('Scopes')
                            ->helperText('Assign scopes to this key. Cannot be edited without rotation.')
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->helperText('Leave empty for no expiration'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $service = app(ApiKeyService::class);

                        return $service->getStatus($record);
                    })
                    ->colors([
                        'success' => 'active',
                        'warning' => 'expired',
                        'danger' => 'revoked',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner_type')
                    ->label('Owner Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner_id')
                    ->label('Owner ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('scopes')
                    ->label('Scopes')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->wrap(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('revoked_at')
                    ->label('Revoked At')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'revoked' => 'Revoked',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === 'active') {
                            return $query->whereNull('revoked_at')
                                ->where(function ($q) {
                                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                });
                        } elseif ($value === 'expired') {
                            return $query->whereNull('revoked_at')
                                ->where('expires_at', '<=', now());
                        } elseif ($value === 'revoked') {
                            return $query->whereNotNull('revoked_at');
                        }

                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('owner_type')
                    ->options([
                        'user' => 'User',
                        'partner' => 'Partner',
                        'service' => 'Service',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('rotate')
                    ->label('Rotate')
                    ->icon('heroicon-o-refresh')
                    ->color('warning')
                    ->action(function ($record) {
                        $service = app(ApiKeyService::class);
                        $result = $service->rotate($record);

                        session()->flash('new_api_key_plaintext', $result['api_key']);
                        session()->flash('new_api_key_id', $result['id']);

                        \Filament\Notifications\Notification::make()
                            ->title('API Key Rotated')
                            ->body('Your new API key has been generated. Copy it now — it will not be shown again.')
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->visible(fn ($record) => app(ApiKeyService::class)->isActive($record) && ! $record->revoked_at),

                Tables\Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-x')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke API Key')
                    ->modalSubheading('This action cannot be undone. The API key will be immediately disabled.')
                    ->action(function ($record) {
                        $service = app(ApiKeyService::class);
                        $service->revoke($record);

                        \Filament\Notifications\Notification::make()
                            ->title('API Key Revoked')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => app(ApiKeyService::class)->isActive($record) && ! $record->revoked_at),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Intentionally empty - don't allow bulk deletion of API keys
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKey::route('/create'),
            'edit' => Pages\EditApiKey::route('/{record}/edit'),
            'view' => Pages\ViewApiKey::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super_admin', 'operator'])) {
            return true;
        }

        if (method_exists($user, 'canAccessFilament') && $user->canAccessFilament()) {
            return true;
        }

        return app()->environment('local') && (int) $user->id === 1;
    }
}
