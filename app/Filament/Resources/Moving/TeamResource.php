<?php

namespace App\Filament\Resources\Moving;

use App\Filament\Resources\Moving\TeamResource\Pages;
use App\Filament\Resources\Moving\TeamResource\RelationManagers;
use App\Models\Moving\Team;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $navigationGroup = 'Moving';
    protected static ?string $navigationLabel = 'Moving Teams';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 504;
    protected static ?string $modelLabel = 'team';
    protected static ?string $pluralModelLabel = 'teams';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Main')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->label('Name')->required()->maxLength(255),
                        Select::make('status')->label('Status')->options(self::statusOptions())->default('active')->required(),
                        Select::make('leader_id')
                            ->label('Leader')
                            ->relationship('leader', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                    ]),
                    Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                ]),
            Section::make('Capacity and Specialization')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('max_orders')->label('Max active orders')->numeric()->default(5)->minValue(1)->required(),
                        TextInput::make('rating')->label('Rating')->numeric()->disabled()->dehydrated(false),
                        TextInput::make('completed_orders_count')->label('Completed orders')->numeric()->disabled()->dehydrated(false),
                    ]),
                    CheckboxList::make('specializations')->label('Specializations')->options(self::specializationOptions())->columns(2),
                    Select::make('executors')
                        ->label('Team members')
                        ->relationship('executors', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                    KeyValue::make('metadata')->label('Metadata')->nullable()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->sortable(),
                TextColumn::make('executors_count')->counts('executors')->label('Members')->sortable(),
                TextColumn::make('activeOrdersCount')
                    ->label('Active orders')
                    ->getStateUsing(fn (Team $record): int => $record->activeOrders()->count())
                    ->sortable(),
                TextColumn::make('max_orders')->label('Order limit')->sortable(),
                TextColumn::make('rating')->label('Rating')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::statusOptions()),
                TernaryFilter::make('has_active_orders')
                    ->label('Has active orders')
                    ->queries(
                        true: fn ($query) => $query->whereHas('activeOrders'),
                        false: fn ($query) => $query->whereDoesntHave('activeOrders'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MovingOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'view' => Pages\ViewTeam::route('/{record}'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }

    protected static function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
        ];
    }

    protected static function specializationOptions(): array
    {
        return [
            'moving' => 'Moving',
            'takelage' => 'Rigging',
            'packing' => 'Packing',
            'disposal' => 'Disposal',
            'cleaning' => 'Cleaning',
            'electronics' => 'Electronics',
        ];
    }
}

