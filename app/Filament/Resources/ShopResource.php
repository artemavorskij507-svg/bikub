<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopResource\Pages;
use App\Modules\Classifieds\Models\Shop;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Str;

class ShopResource extends Resource
{
    protected static ?string $model = Shop::class;

    // Используем иконку, которая есть в Filament v2 heroicons
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Classifieds';

    protected static ?string $navigationLabel = 'Shops';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('General Info')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->required(),

                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            if (! empty($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Branding')
                ->schema([
                    Forms\Components\FileUpload::make('logo_path')
                        ->image()
                        ->directory('shops/logos'),

                    Forms\Components\FileUpload::make('cover_path')
                        ->image()
                        ->directory('shops/covers'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Verified Business'),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner'),

                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified'),

                Tables\Columns\TextColumn::make('ads_count')
                    ->counts('ads')
                    ->label('Ads'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShops::route('/'),
            'create' => Pages\CreateShop::route('/create'),
            'edit' => Pages\EditShop::route('/{record}/edit'),
        ];
    }
}
