<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminIpRuleResource\Pages;
use App\Models\AdminIpRule;
use App\Rules\IpOrCidr;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form as ResourceForm;
use Filament\Resources\Resource;
use Filament\Resources\Table as ResourceTable;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;

class AdminIpRuleResource extends Resource
{
    protected static ?string $model = AdminIpRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Admin IP Rules';

    public static function form(ResourceForm $form): ResourceForm
    {
        return $form
            ->schema([
                Placeholder::make('current_ip')
                    ->content(fn (): string => 'Ваш текущий IP: '.request()->ip())
                    ->label('Current IP'),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'allow' => 'Allow',
                        'deny' => 'Deny',
                    ])
                    ->required(),

                TextInput::make('ip_range')
                    ->label('IP or CIDR')
                    ->required()
                    ->rules([new IpOrCidr])
                    ->helperText('Examples: 203.0.113.5 or 203.0.113.0/24'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('If allow rules exist — access is restricted ONLY to them'),

                Forms\Components\Checkbox::make('confirm_lockdown')
                    ->label('I understand that creating the first active allow rule will restrict admin access to this IP')
                    ->visible(fn (callable $get) => $get('type') === 'allow'),
            ]);
    }

    public static function table(ResourceTable $table): ResourceTable
    {
        return $table
            ->columns([
                BadgeColumn::make('type')
                    ->colors([
                        'success' => 'allow',
                        'danger' => 'deny',
                    ])
                    ->sortable(),

                TextColumn::make('ip_range')->searchable()->sortable(),
                TextColumn::make('description')->limit(50)->wrap(),
                ToggleColumn::make('is_active')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Confirm delete')
                    ->modalSubheading('This action cannot be undone. Are you sure you want to delete this IP rule?'),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'allow' => 'Allow',
                    'deny' => 'Deny',
                ]),
                SelectFilter::make('is_active')->options([
                    1 => 'Active',
                    0 => 'Inactive',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminIpRules::route('/'),
            'create' => Pages\CreateAdminIpRule::route('/create'),
            'edit' => Pages\EditAdminIpRule::route('/{record}/edit'),
        ];
    }

    // Access control: only role=admin
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
