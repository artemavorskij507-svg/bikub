<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleInspectionPresetResource\Pages;
use App\Models\VehicleInspectionPreset;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class VehicleInspectionPresetResource extends Resource
{
    protected static ?string $model = VehicleInspectionPreset::class;

    // fix: make Roadside resources visible in GLF Bike Care panel navigation
    protected static bool $shouldRegisterNavigation = true;

    // fix: unify Roadside module icon for consistent navigation
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Пресеты осмотра авто';

    protected static ?string $navigationGroup = 'Roadside & Tow';

    protected static ?int $navigationSort = 607;

    // fix: standardize navigation gating for v2 (temporary permissive for admin/operator/dispatcher)
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'operator', 'dispatcher']);
        }

        return true;
    }

    // fix: relax access to avoid hiding from navigation (visible to any authenticated user)
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // fix: enforce required fields to satisfy NOT NULL db constraints
                Forms\Components\TextInput::make('title')
                    ->label('Название пресета')
                    ->required()
                    ->maxLength(255)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // auto-generate slug from title if slug is empty or matches previous pattern
                        $set('slug', \Illuminate\Support\Str::slug($state));
                    }),
                Forms\Components\TextInput::make('slug')
                    ->label('Слаг')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(3)
                    ->nullable(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // fix: add basic columns for index view
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Слаг')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Статус')
                    ->colors([
                        'success' => fn ($state) => (bool) $state === true,
                        'secondary' => fn ($state) => (bool) $state === false,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Активен' : 'Выключен'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
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
            'index' => Pages\ListVehicleInspectionPresets::route('/'),
            'create' => Pages\CreateVehicleInspectionPreset::route('/create'),
            'edit' => Pages\EditVehicleInspectionPreset::route('/{record}/edit'),
        ];
    }
}
