<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    protected static ?string $title = 'Договоры';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->required(),
            Forms\Components\Select::make('status')
                ->options(['draft' => 'Черновик', 'active' => 'Активен', 'suspended' => 'Приостановлен', 'expired' => 'Истёк'])
                ->required(),
            Forms\Components\DatePicker::make('valid_from'),
            Forms\Components\DatePicker::make('valid_to'),
            Forms\Components\DatePicker::make('insurance_valid_to')->label('Страховка до'),
            Forms\Components\FileUpload::make('document_path')->label('Документ')->directory('contracts')->visibility('private'),
            Forms\Components\KeyValue::make('terms')->label('Условия'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->searchable(),
            Tables\Columns\BadgeColumn::make('status')
                ->colors(['warning' => 'draft', 'success' => 'active', 'danger' => 'suspended', 'gray' => 'expired']),
            Tables\Columns\TextColumn::make('valid_from')->date(),
            Tables\Columns\TextColumn::make('valid_to')->date(),
            Tables\Columns\TextColumn::make('insurance_valid_to')->date()->label('Страховка'),
        ])->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
