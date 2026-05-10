<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdFeatureResource\Pages;
use App\Modules\Classifieds\Models\AdFeature;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class AdFeatureResource extends Resource
{
    protected static ?string $model = AdFeature::class;

    // Use icon that exists in Filament v2 heroicons set
    protected static ?string $navigationIcon = 'heroicon-o-adjustments';

    protected static ?string $navigationLabel = 'Ad Features';

    protected static ?string $navigationGroup = 'Classifieds';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(100),

            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('field_type')
                ->label('Field type')
                ->options([
                    'text' => 'Text',
                    'select' => 'Select',
                    'checkbox' => 'Checkbox',
                    'number' => 'Number',
                    'textarea' => 'Textarea',
                ])
                ->required()
                ->reactive(),

            Forms\Components\Repeater::make('options')
                ->schema([
                    Forms\Components\TextInput::make('label')
                        ->label('Label')
                        ->required(),
                    Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->required(),
                ])
                ->columns(2)
                ->visible(fn ($get) => $get('field_type') === 'select')
                ->helperText('Опции для выпадающего списка (используются только для типа "Select").'),

            Forms\Components\Toggle::make('is_required')
                ->label('Required field'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable(),
                Tables\Columns\TextColumn::make('field_type')
                    ->label('Type'),
                Tables\Columns\IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdFeatures::route('/'),
            'create' => Pages\CreateAdFeature::route('/create'),
            'edit' => Pages\EditAdFeature::route('/{record}/edit'),
        ];
    }
}
