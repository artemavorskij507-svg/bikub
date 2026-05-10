<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdImportResource\Pages;
use App\Modules\Classifieds\Models\AdImport;
use App\Modules\Classifieds\Models\Shop;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class AdImportResource extends Resource
{
    protected static ?string $model = AdImport::class;

    // Используем иконку, которая существует в Filament v2 heroicons
    protected static ?string $navigationIcon = 'heroicon-o-upload';

    protected static ?string $navigationGroup = 'Classifieds';

    protected static ?string $navigationLabel = 'Bulk Import';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Upload Feed')
                ->schema([
                    Forms\Components\Select::make('shop_id')
                        ->label('Target Shop (Optional)')
                        ->options(Shop::pluck('name', 'id'))
                        ->searchable(),

                    Forms\Components\FileUpload::make('file_path')
                        ->label('XML File')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/xml', 'application/xml'])
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'processing',
                        'danger' => 'failed',
                        'secondary' => 'pending',
                    ]),

                Tables\Columns\TextColumn::make('processed_count')
                    ->label('Items'),

                Tables\Columns\TextColumn::make('error_count')
                    ->label('Errors')
                    ->color('danger'),
            ])
            ->actions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdImports::route('/'),
            'create' => Pages\CreateAdImport::route('/create'),
        ];
    }
}
