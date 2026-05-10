<?php

namespace App\Filament\Resources\Moving;

use App\Filament\Resources\Moving\MovingOrderPhotoResource\Pages;
use App\Models\Moving\MovingOrderPhoto;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

class MovingOrderPhotoResource extends Resource
{
    protected static ?string $model = MovingOrderPhoto::class;

    protected static ?string $navigationGroup = 'Moving';

    protected static ?string $navigationLabel = 'Moving Order Photos';

    protected static ?string $navigationIcon = 'heroicon-o-photograph';

    protected static ?int $navigationSort = 505;

    protected static ?string $modelLabel = 'moving order photo';

    protected static ?string $pluralModelLabel = 'moving order photos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('moving_order_id')
                ->label('Moving order')
                ->relationship('movingOrder', 'id')
                ->searchable()
                ->required(),
            Forms\Components\FileUpload::make('file_path')
                ->label('Photo')
                ->disk('public')
                ->directory('moving-orders/photos')
                ->required(),
            Forms\Components\Select::make('collection_name')
                ->label('Collection')
                ->options([
                    'pre_move_photos' => 'Pre-move',
                    'post_move_photos' => 'Post-move',
                    'damage_photos' => 'Damage',
                ])
                ->default('pre_move_photos')
                ->required(),
            Forms\Components\TextInput::make('file_name')
                ->label('File name')
                ->maxLength(255),
            Forms\Components\TextInput::make('mime_type')
                ->label('MIME type')
                ->maxLength(255),
            Forms\Components\TextInput::make('file_size')
                ->label('Size (bytes)')
                ->numeric(),
            Forms\Components\TextInput::make('latitude')
                ->label('Latitude')
                ->numeric(),
            Forms\Components\TextInput::make('longitude')
                ->label('Longitude')
                ->numeric(),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('movingOrder.id')
                    ->label('Order')
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('file_path')
                    ->label('Preview')
                    ->disk('public')
                    ->height(40),
                TextColumn::make('collection_name')
                    ->label('Collection')
                    ->formatStateUsing(fn ($state = null) => match ((string) $state) {
                        'pre_move_photos' => 'Pre-move',
                        'post_move_photos' => 'Post-move',
                        'damage_photos' => 'Damage',
                        default => (string) $state,
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovingOrderPhotos::route('/'),
            'create' => Pages\CreateMovingOrderPhoto::route('/create'),
            'edit' => Pages\EditMovingOrderPhoto::route('/{record}/edit'),
        ];
    }
}

