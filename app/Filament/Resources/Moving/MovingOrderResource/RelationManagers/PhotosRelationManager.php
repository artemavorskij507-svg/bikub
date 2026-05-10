<?php

namespace App\Filament\Resources\Moving\MovingOrderResource\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $recordTitleAttribute = 'file_name';

    public static function getLabel(): ?string
    {
        return 'Фото';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Фото';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file_path')
                    ->label('Файл')
                    ->disk('public')
                    ->directory('moving-orders/photos')
                    ->preserveFilenames()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                        return now()->format('YmdHis').'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
                    })
                    ->required(),
                Select::make('collection_name')
                    ->label('Колекція')
                    ->options([
                        'pre_move_photos' => 'Перед переїздом',
                        'post_move_photos' => 'Після переїзду',
                        'damage_photos' => 'Пошкодження',
                    ])
                    ->required()
                    ->default('pre_move_photos'),
                TextInput::make('latitude')
                    ->label('Широта')
                    ->numeric()
                    ->nullable(),
                TextInput::make('longitude')
                    ->label('Довгота')
                    ->numeric()
                    ->nullable(),
                Textarea::make('description')
                    ->label('Опис')
                    ->columnSpanFull()
                    ->nullable(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('file_path')
                    ->disk('public')
                    ->label('Превʼю'),
                TextColumn::make('collection_name')
                    ->label('Колекція')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pre_move_photos' => 'Перед переїздом',
                        'post_move_photos' => 'Після переїзду',
                        'damage_photos' => 'Пошкодження',
                        default => $state,
                    }),
                TextColumn::make('latitude')
                    ->label('Lat')
                    ->toggleable(),
                TextColumn::make('longitude')
                    ->label('Lng')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Завантажено')
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}