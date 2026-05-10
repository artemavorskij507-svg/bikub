<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns as C;

class ApiKeysRelationManager extends RelationManager
{
    protected static string $relationship = 'apiKeys';

    protected static ?string $title = 'API Keys';

    public static function table(Table $table): Table
    {
        return $table->columns([
            C\TextColumn::make('prefix')->label('Prefix'),
            C\IconColumn::make('is_active')->boolean()->label('Active'),
            C\TextColumn::make('rate_limit_per_min')->label('Rate/min'),
            C\TextColumn::make('last_used_at')->since()->label('Last used'),
        ])->headerActions([
            Tables\Actions\CreateAction::make()->form([
                Forms\Components\TextInput::make('prefix')->default('pk_live_')->required(),
                Forms\Components\TextInput::make('key_hash')->password()->label('Secret (hash)')->required(),
                Forms\Components\TextInput::make('rate_limit_per_min')->numeric()->default(120),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]),
        ])->actions([
            Tables\Actions\EditAction::make()->form([
                Forms\Components\TextInput::make('rate_limit_per_min')->numeric(),
                Forms\Components\Toggle::make('is_active'),
            ]),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
