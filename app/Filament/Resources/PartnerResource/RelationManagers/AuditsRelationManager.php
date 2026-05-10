<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns as C;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'Audits';

    public static function table(Table $table): Table
    {
        return $table->columns([
            C\TextColumn::make('created_at')->since()->label('Time'),
            C\TextColumn::make('actor_id')->label('Actor'),
            C\BadgeColumn::make('action')->label('Action'),
        ])->actions([
            Tables\Actions\ViewAction::make(),
        ]);
    }
}
