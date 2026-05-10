<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceCategoryResource\Pages;
use App\Models\ServiceCategory;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ServiceCategoryResource extends Resource
{
    protected static ?string $model = ServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $pluralLabel = 'Categories';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic information')
                    ->description('Core data about service category')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Category code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Unique category code, e.g. delivery, moving, handyman')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('canonical_code')
                            ->label('Canonical code')
                            ->maxLength(50)
                            ->helperText('Canonical code for integrations')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('name')
                            ->label('Category name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if (! filled($state)) {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            })
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('slug')
                            ->label('URL slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('URL-friendly identifier, auto-generated from name')
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('short_description')
                            ->label('Short description')
                            ->rows(2)
                            ->maxLength(255)
                            ->helperText('Short text for cards and compact lists')
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('description')
                            ->label('Full description')
                            ->rows(4)
                            ->helperText('Detailed category description')
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Visual settings')
                    ->description('Icon, color and display style')
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon (class or name)')
                            ->maxLength(100)
                            ->helperText('Example: heroicon-o-truck or CSS class name')
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('icon_svg')
                            ->label('SVG icon')
                            ->rows(4)
                            ->helperText('Custom SVG icon that overrides icon field')
                            ->columnSpan(1),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Category color')
                            ->helperText('Main color used in UI')
                            ->columnSpan(1),
                        Forms\Components\Placeholder::make('preview')
                            ->label('Preview')
                            ->content(function ($get) {
                                $color = $get('color') ?? '#3b82f6';
                                $name = $get('name') ?? 'Category name';
                                $icon = $get('icon') ?? 'heroicon-o-folder';

                                if (! str_starts_with($color, '#')) {
                                    $colorMap = [
                                        'blue' => '#3b82f6',
                                        'red' => '#ef4444',
                                        'green' => '#10b981',
                                        'yellow' => '#f59e0b',
                                        'purple' => '#8b5cf6',
                                        'pink' => '#ec4899',
                                        'indigo' => '#6366f1',
                                        'orange' => '#f97316',
                                    ];
                                    $color = $colorMap[$color] ?? '#3b82f6';
                                }

                                return new \Illuminate\Support\HtmlString(view('filament.components.category-preview-live', [
                                    'color' => $color,
                                    'name' => $name,
                                    'icon' => $icon,
                                ])->render());
                            })
                            ->reactive()
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Display settings')
                    ->description('Visibility and ordering options')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only active categories are visible in app')
                            ->default(true)
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('show_on_homepage')
                            ->label('Show on homepage')
                            ->helperText('Display this category on homepage')
                            ->default(false)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort order')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->helperText('Smaller value appears first')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('homepage_order')
                            ->label('Homepage order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Sort order on homepage')
                            ->columnSpan(1)
                            ->visible(fn ($get) => $get('show_on_homepage')),
                        Forms\Components\TextInput::make('order_column')
                            ->label('Extra order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Optional additional sorting parameter')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Pricing')
                    ->description('Default pricing settings for this category')
                    ->schema([
                        Forms\Components\TextInput::make('default_pricing_group')
                            ->label('Default pricing group')
                            ->maxLength(50)
                            ->helperText('Pricing group applied by default to services in this category')
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Metadata')
                    ->description('Additional JSON key-value metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata (JSON)')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Additional category attributes in key-value format')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Statistics')
                    ->description('Linked services metrics')
                    ->schema([
                        Forms\Components\View::make('filament.components.category-stats-live')
                            ->label('Statistics')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Color'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort order')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('serviceTypes_count')
                    ->label('Services')
                    ->counts('serviceTypes')
                    ->sortable()
                    ->colors([
                        'success' => fn ($record) => $record && ($record->serviceTypes_count ?? 0) > 0,
                        'warning' => fn ($record) => $record && ($record->serviceTypes_count ?? 0) === 0,
                    ]),
                Tables\Columns\BadgeColumn::make('active_service_types_count')
                    ->label('Active services')
                    ->getStateUsing(fn ($record) => $record ? $record->serviceTypes()->where('is_active', true)->count() : 0)
                    ->color('info')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
                Tables\Filters\TernaryFilter::make('has_services')
                    ->label('Has services')
                    ->placeholder('All categories')
                    ->trueLabel('With services')
                    ->falseLabel('Without services')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('serviceTypes'),
                        false: fn (Builder $query) => $query->whereDoesntHave('serviceTypes'),
                        blank: fn (Builder $query) => $query,
                    )
                    ->default(null),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('move_up')
                    ->label('Up')
                    ->icon('heroicon-o-arrow-up')
                    ->action(function (ServiceCategory $record) {
                        $prev = ServiceCategory::where('sort_order', '<', $record->sort_order)
                            ->orderBy('sort_order', 'desc')
                            ->first();
                        if ($prev) {
                            $temp = $record->sort_order;
                            $record->update(['sort_order' => $prev->sort_order]);
                            $prev->update(['sort_order' => $temp]);
                            \Filament\Notifications\Notification::make()
                                ->title('Order updated')
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn (ServiceCategory $record) => ServiceCategory::where('sort_order', '<', $record->sort_order)->exists()),
                Tables\Actions\Action::make('move_down')
                    ->label('Down')
                    ->icon('heroicon-o-arrow-down')
                    ->action(function (ServiceCategory $record) {
                        $next = ServiceCategory::where('sort_order', '>', $record->sort_order)
                            ->orderBy('sort_order', 'asc')
                            ->first();
                        if ($next) {
                            $temp = $record->sort_order;
                            $record->update(['sort_order' => $next->sort_order]);
                            $next->update(['sort_order' => $temp]);
                            \Filament\Notifications\Notification::make()
                                ->title('Order updated')
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn (ServiceCategory $record) => ServiceCategory::where('sort_order', '>', $record->sort_order)->exists()),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (ServiceCategory $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (ServiceCategory $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (ServiceCategory $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (ServiceCategory $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->is_active ? 'Category activated' : 'Category deactivated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Export (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'categories_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Code', 'Name', 'Slug', 'Active', 'Sort order', 'Services']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->code,
                                    $record->name,
                                    $record->slug,
                                    $record->is_active ? 'Yes' : 'No',
                                    $record->sort_order,
                                    $record->serviceTypes()->count(),
                                ]);
                            }
                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
                Tables\Actions\BulkAction::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => true]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Categories activated')
                            ->body('Updated: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => false]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Categories deactivated')
                            ->body('Updated: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceCategories::route('/'),
            'create' => Pages\CreateServiceCategory::route('/create'),
            'edit' => Pages\EditServiceCategory::route('/{record}/edit'),
        ];
    }
}
