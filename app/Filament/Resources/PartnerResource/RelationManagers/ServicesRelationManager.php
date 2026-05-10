<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Услуги';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('pivot.base_fee_cents')->label('База (NOK)')->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2).' NOK' : 'N/A'),
            Tables\Columns\TextColumn::make('pivot.per_km_cents')->label('за км (NOK)')->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2).' NOK' : 'N/A'),
            Tables\Columns\TextColumn::make('pivot.sla_minutes')->label('SLA мин'),
            Tables\Columns\IconColumn::make('pivot.is_active')->boolean()->label('Вкл'),
        ])->headerActions([
            Tables\Actions\AttachAction::make()
                ->recordTitleAttribute('name')
                ->preloadRecordSelect()
                ->form(fn ($action) => [
                    $action->getRecordSelect(),
                    Forms\Components\TextInput::make('base_fee_cents')->numeric()->default(0),
                    Forms\Components\TextInput::make('per_km_cents')->numeric()->default(0),
                    Forms\Components\TextInput::make('sla_minutes')->numeric()->default(60),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ]),
        ])->actions([
            Tables\Actions\EditAction::make()->form([
                Forms\Components\TextInput::make('base_fee_cents')->numeric(),
                Forms\Components\TextInput::make('per_km_cents')->numeric(),
                Forms\Components\TextInput::make('sla_minutes')->numeric(),
                Forms\Components\Toggle::make('is_active'),
            ]),
            Tables\Actions\DetachAction::make(),
        ]);
    }
}
