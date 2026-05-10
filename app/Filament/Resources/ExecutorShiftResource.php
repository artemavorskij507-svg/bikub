<?php

namespace App\Filament\Resources;

use App\Domain\Dispatch\Models\ExecutorShift;
use App\Filament\Resources\ExecutorShiftResource\Pages;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ExecutorShiftResource extends Resource
{
    protected static ?string $model = ExecutorShift::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Операции';
    protected static ?string $navigationLabel = 'Executor Shifts';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('executor_id')->label('Executor')->relationship('executor', 'name')->searchable()->required(),
            Forms\Components\Select::make('day_of_week')->options([1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',0=>'Sunday'])->required(),
            Forms\Components\TimePicker::make('start_time')->required(),
            Forms\Components\TimePicker::make('end_time')->required()->helperText('Overnight shift is allowed (e.g. 22:00 -> 06:00).'),
            Forms\Components\TextInput::make('timezone')->default('Europe/Kiev')->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('executor.name')->label('Executor')->searchable(),
            Tables\Columns\TextColumn::make('day_of_week')->formatStateUsing(fn ($state) => [0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'][(int)$state] ?? (string)$state),
            Tables\Columns\TextColumn::make('start_time'),
            Tables\Columns\TextColumn::make('end_time'),
            Tables\Columns\TextColumn::make('timezone'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('updated_at')->since(),
        ])->filters([
            Tables\Filters\TernaryFilter::make('is_active'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('executor');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExecutorShifts::route('/'),
            'create' => Pages\CreateExecutorShift::route('/create'),
            'edit' => Pages\EditExecutorShift::route('/{record}/edit'),
        ];
    }
}
