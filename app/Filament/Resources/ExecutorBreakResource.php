<?php

namespace App\Filament\Resources;

use App\Domain\Dispatch\Models\ExecutorBreak;
use App\Filament\Resources\ExecutorBreakResource\Pages;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class ExecutorBreakResource extends Resource
{
    protected static ?string $model = ExecutorBreak::class;
    protected static ?string $navigationIcon = 'heroicon-o-pause';
    protected static ?string $navigationGroup = 'Операции';
    protected static ?string $navigationLabel = 'Executor Breaks';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('executor_id')->relationship('executor', 'name')->searchable()->required(),
            Forms\Components\DatePicker::make('shift_date')->required(),
            Forms\Components\DateTimePicker::make('break_start_at')->required(),
            Forms\Components\DateTimePicker::make('break_end_at')->required()->after('break_start_at'),
            Forms\Components\TextInput::make('type')->default('break')->required(),
            Forms\Components\Toggle::make('is_paid')->default(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('executor.name')->label('Executor')->searchable(),
            Tables\Columns\TextColumn::make('shift_date')->date(),
            Tables\Columns\TextColumn::make('break_start_at')->dateTime(),
            Tables\Columns\TextColumn::make('break_end_at')->dateTime(),
            Tables\Columns\TextColumn::make('type'),
            Tables\Columns\IconColumn::make('is_paid')->boolean(),
            Tables\Columns\TextColumn::make('updated_at')->since(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->defaultSort('break_start_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExecutorBreaks::route('/'),
            'create' => Pages\CreateExecutorBreak::route('/create'),
            'edit' => Pages\EditExecutorBreak::route('/{record}/edit'),
        ];
    }
}
