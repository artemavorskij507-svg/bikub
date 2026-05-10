<?php

namespace App\Filament\Resources\VirtualOffice;

use App\Filament\Resources\VirtualOffice\AgentResource\Pages;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Schema;

class AgentResource extends Resource
{
    protected static ?string $model = \App\Models\User::class;
    private const LEGACY_MODEL = \App\Models\VirtualOffice\Agent::class;
    private const FALLBACK_MODEL = \App\Modules\AgencyAgents\Models\Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Virtual Office Agents';

    protected static ?string $navigationGroup = 'Virtual Office';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return self::resolvedModelClass() !== null
            && Schema::hasTable(self::resolvedTableName());
    }

    public static function canViewAny(): bool
    {
        if (self::resolvedModelClass() === null || ! Schema::hasTable(self::resolvedTableName())) {
            return false;
        }

        return parent::canViewAny();
    }

    public static function getModel(): string
    {
        return self::resolvedModelClass() ?? \App\Models\User::class;
    }

    public static function form(Form $form): Form
    {
        $agentTable = self::resolvedTableName();
        $hasCategoriesTable = self::hasLegacyVirtualOfficeAgentModel()
            && Schema::hasTable('categories')
            && Schema::hasColumn('categories', 'name')
            && Schema::hasColumn($agentTable, 'category_id');
        $hasOfficeZonesTable = self::hasLegacyVirtualOfficeAgentModel()
            && Schema::hasTable('office_zones')
            && Schema::hasColumn('office_zones', 'name')
            && Schema::hasColumn($agentTable, 'zone_id');

        $schema = [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')
                ->rows(3),
        ];

        if ($hasCategoriesTable) {
            $schema[] = Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->searchable()
                ->preload();
        }

        if ($hasOfficeZonesTable) {
            $schema[] = Forms\Components\Select::make('zone_id')
                ->relationship('zone', 'name')
                ->label('Zone')
                ->searchable()
                ->preload();
        }

        $schema[] = Forms\Components\Toggle::make('is_active')
            ->label('Active')
            ->default(true);

        $schema[] = Forms\Components\TextInput::make('emoji')
            ->maxLength(255);

        $schema[] = Forms\Components\TextInput::make('color')
            ->maxLength(255);

        $schema[] = Forms\Components\TextInput::make('avatar')
            ->maxLength(255);

        $schema[] = Forms\Components\TextInput::make('source_file')
            ->maxLength(255);

        $schema[] = Forms\Components\KeyValue::make('config')
            ->label('Config');

        return $form
            ->schema([
                Forms\Components\Section::make('Agent Information')
                    ->schema($schema)
                    ->columns(2),
                Forms\Components\Section::make('Position')
                    ->schema([
                        Forms\Components\TextInput::make('x_position')
                            ->numeric()
                            ->label('X Position'),
                        Forms\Components\TextInput::make('y_position')
                            ->numeric()
                            ->label('Y Position'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $agentTable = self::resolvedTableName();
        $hasCategoriesTable = self::hasLegacyVirtualOfficeAgentModel()
            && Schema::hasTable('categories')
            && Schema::hasColumn('categories', 'name')
            && Schema::hasColumn($agentTable, 'category_id');
        $hasOfficeZonesTable = self::hasLegacyVirtualOfficeAgentModel()
            && Schema::hasTable('office_zones')
            && Schema::hasColumn('office_zones', 'name')
            && Schema::hasColumn($agentTable, 'zone_id');
        $hasTasksTable = Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'agent_id');

        $columns = [
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('slug')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        if ($hasCategoriesTable) {
            $columns[] = Tables\Columns\TextColumn::make('category.name')
                ->label('Category')
                ->sortable()
                ->toggleable();
        }

        if ($hasOfficeZonesTable) {
            $columns[] = Tables\Columns\TextColumn::make('zone.name')
                ->label('Zone')
                ->sortable()
                ->toggleable();
        }

        $columns[] = Tables\Columns\IconColumn::make('is_active')
            ->label('Active')
            ->boolean();

        if ($hasTasksTable) {
            $columns[] = Tables\Columns\TextColumn::make('tasks_count')
                ->counts('tasks')
                ->label('Tasks')
                ->sortable();
        }

        $columns[] = Tables\Columns\TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $filters = [
            Tables\Filters\TernaryFilter::make('is_active')
                ->label('Active'),
        ];

        if ($hasCategoriesTable) {
            $filters[] = Tables\Filters\SelectFilter::make('category_id')
                ->relationship('category', 'name')
                ->label('Category');
        }

        if ($hasOfficeZonesTable) {
            $filters[] = Tables\Filters\SelectFilter::make('zone_id')
                ->relationship('zone', 'name')
                ->label('Zone');
        }

        return $table
            ->columns($columns)
            ->filters($filters)
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'view' => Pages\ViewAgent::route('/{record}'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }

    private static function hasLegacyVirtualOfficeAgentModel(): bool
    {
        return class_exists(self::LEGACY_MODEL);
    }

    private static function hasFallbackAgentModel(): bool
    {
        return class_exists(self::FALLBACK_MODEL);
    }

    private static function resolvedModelClass(): ?string
    {
        if (self::hasLegacyVirtualOfficeAgentModel()) {
            return self::LEGACY_MODEL;
        }

        if (self::hasFallbackAgentModel()) {
            return self::FALLBACK_MODEL;
        }

        return null;
    }

    private static function resolvedTableName(): string
    {
        if (self::hasLegacyVirtualOfficeAgentModel()) {
            return 'agents';
        }

        if (self::hasFallbackAgentModel()) {
            return 'agency_agents';
        }

        return 'agents';
    }
}
