<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class ZonesRelationManager extends RelationManager
{
    protected static string $relationship = 'zones';

    protected static ?string $title = 'Зоны';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('pivot.window')->label('Окна')->toggleable(),
        ])->headerActions([Tables\Actions\AttachAction::make()->recordTitleAttribute('name')->preloadRecordSelect()])
            ->actions([Tables\Actions\DetachAction::make()]);
    }
}
