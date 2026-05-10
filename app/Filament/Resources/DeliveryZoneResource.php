<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryZoneResource\Pages;
use App\Models\DeliveryZone;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Delivery';

    protected static ?int $navigationSort = 210;

    protected static ?string $navigationLabel = 'Delivery Zones';

    protected static ?string $modelLabel = 'Delivery Zone';

    protected static ?string $pluralModelLabel = 'Delivery Zones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Zone Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'polygon' => 'Polygon (Area)',
                                'circle' => 'Circle (Radius)',
                            ])
                            ->required()
                            ->default('polygon')
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                        TextInput::make('delivery_fee')
                            ->label('Delivery Fee (CHF)')
                            ->numeric()
                            ->required()
                            ->default(0.00)
                            ->step(0.01)
                            ->columnSpan(1),
                        TextInput::make('delivery_time_minutes')
                            ->label('Estimated Time (min)')
                            ->numeric()
                            ->required()
                            ->default(30)
                            ->step(1)
                            ->columnSpan(1),
                    ]),
                Card::make()
                    ->schema([
                        Textarea::make('coordinates')
                            ->label('Coordinates (JSON Array)')
                            ->helperText('JSON array format: [[lat, lng], [lat, lng], ...]')
                            ->rows(5)
                            ->columnSpanFull(),
                        TextInput::make('center_lat')
                            ->label('Center Latitude')
                            ->numeric()
                            ->columnSpan(1),
                        TextInput::make('center_lng')
                            ->label('Center Longitude')
                            ->numeric()
                            ->columnSpan(1),
                        TextInput::make('radius_km')
                            ->label('Radius (km)')
                            ->numeric()
                            ->step(0.01)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Zone Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'polygon' => 'Polygon',
                        'circle' => 'Circle',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('delivery_fee')
                    ->label('Fee (CHF)')
                    ->formatStateUsing(fn ($state) => 'CHF '.number_format((float) $state, 2, ',', ' '))
                    ->sortable(),
                TextColumn::make('delivery_time_minutes')
                    ->label('Time (min)')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Zone Type')
                    ->options([
                        'polygon' => 'Polygon (Area)',
                        'circle' => 'Circle (Radius)',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Only Active')
                    ->falseLabel('Only Inactive')
                    ->placeholder('All Zones'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListDeliveryZones::route('/'),
            'create' => Pages\CreateDeliveryZone::route('/create'),
            'edit' => Pages\EditDeliveryZone::route('/{record}/edit'),
        ];
    }
}
