<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeoZoneResource\Pages;
use App\Models\GeoZone;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class GeoZoneResource extends Resource
{
    protected static ?string $model = GeoZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe';

    protected static ?string $navigationGroup = 'Справочники и контент';

    protected static ?int $navigationSort = 110;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('org_id')
                    ->default(fn () => auth()->user()->default_org_id ?? 1)
                    ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 1)),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')
                    ->maxLength(10000),
                Forms\Components\Select::make('type')
                    ->label('Тип зоны')
                    ->options([
                        'circle' => 'Круг',
                        'polygon' => 'Полигон',
                        'bbox' => 'Bounding Box',
                        'multi' => 'Множественная',
                    ])
                    ->default('polygon')
                    ->required()
                    ->reactive(),
                Forms\Components\TextInput::make('center_latitude')
                    ->required()
                    ->numeric()
                    ->rule('between:-90,90')
                    ->dehydrateStateUsing(fn ($state) => $state !== '' ? (float) $state : null),
                Forms\Components\TextInput::make('center_longitude')
                    ->required()
                    ->numeric()
                    ->rule('between:-180,180')
                    ->dehydrateStateUsing(fn ($state) => $state !== '' ? (float) $state : null),
                Forms\Components\TextInput::make('radius_meters')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->dehydrateStateUsing(fn ($state) => $state !== '' ? (int) $state : null),
                Forms\Components\Textarea::make('geometry')
                    ->label('Geometry (GeoJSON)')
                    ->helperText('GeoJSON объект: для circle {"center":[lat,lng],"radius_m":60000}, для polygon {"type":"Polygon","coordinates":[[[lng,lat],...]]}')
                    ->rows(8)
                    ->dehydrateStateUsing(function ($state) {
                        if (is_array($state)) {
                            return json_encode($state, JSON_PRETTY_PRINT);
                        }
                        if (is_string($state)) {
                            $trim = trim($state);
                            if ($trim === '') {
                                return null;
                            }
                            $decoded = json_decode($trim, true);

                            return json_last_error() === JSON_ERROR_NONE ? $trim : null;
                        }

                        return null;
                    })
                    ->afterStateHydrated(function ($component, $state) {
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $component->state(json_last_error() === JSON_ERROR_NONE ? $decoded : $state);
                        }
                    }),
                Forms\Components\TextInput::make('priority')
                    ->label('Приоритет')
                    ->numeric()
                    ->default(100)
                    ->helperText('Меньше = выше приоритет'),
                Forms\Components\Textarea::make('polygon_coordinates')
                    ->label('Polygon Coordinates (legacy)')
                    ->helperText('JSON массив координат или оставьте пустым (legacy поле)')
                    ->dehydrateStateUsing(function ($state) {
                        if (is_array($state)) {
                            return $state;
                        }
                        if (is_string($state)) {
                            $trim = trim($state);
                            if ($trim === '') {
                                return null;
                            }
                            $decoded = json_decode($trim, true);

                            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                        }

                        return null;
                    }),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Forms\Components\Textarea::make('meta')
                    ->label('Meta (JSON)')
                    ->helperText('JSON объект с метаданными (например: {"pricing_group":"zone_a","max_distance_km":60})')
                    ->rows(4)
                    ->dehydrateStateUsing(function ($state) {
                        if (is_array($state)) {
                            return json_encode($state, JSON_PRETTY_PRINT);
                        }
                        if (is_string($state)) {
                            $trim = trim($state);
                            if ($trim === '') {
                                return null;
                            }
                            $decoded = json_decode($trim, true);

                            return json_last_error() === JSON_ERROR_NONE ? $trim : null;
                        }

                        return null;
                    })
                    ->afterStateHydrated(function ($component, $state) {
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $component->state(json_last_error() === JSON_ERROR_NONE ? $decoded : $state);
                        }
                    }),
                Forms\Components\TextInput::make('source_file')
                    ->label('Source File')
                    ->helperText('Путь к файлу-источнику данных'),
                Forms\Components\Textarea::make('metadata')
                    ->label('Metadata (legacy)')
                    ->helperText('JSON объект или оставьте пустым (legacy поле)')
                    ->dehydrateStateUsing(function ($state) {
                        if (is_array($state)) {
                            return $state;
                        }
                        if (is_string($state)) {
                            $trim = trim($state);
                            if ($trim === '') {
                                return null;
                            }
                            $decoded = json_decode($trim, true);

                            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                        }

                        return null;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'circle' => 'success',
                        'polygon' => 'primary',
                        'bbox' => 'warning',
                        'multi' => 'danger',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Приоритет')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип зоны')
                    ->multiple()
                    ->options([
                        'circle' => 'Круг',
                        'polygon' => 'Полигон',
                        'bbox' => 'Bounding Box',
                        'multi' => 'Множественная',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна')
                    ->placeholder('Все')
                    ->trueLabel('Активна')
                    ->falseLabel('Неактивна'),
                Tables\Filters\Filter::make('priority_range')
                    ->label('Приоритет')
                    ->form([
                        Forms\Components\TextInput::make('priority_from')
                            ->label('От')
                            ->numeric(),
                        Forms\Components\TextInput::make('priority_to')
                            ->label('До')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['priority_from'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('priority', '>=', $value),
                            )
                            ->when(
                                $data['priority_to'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('priority', '<=', $value),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (GeoZone $record) => $record->is_active ? 'Деактивировать' : 'Активировать')
                    ->icon(fn (GeoZone $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (GeoZone $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (GeoZone $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->is_active ? 'Зона активирована' : 'Зона деактивирована')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'geo_zones_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Название', 'Slug', 'Тип', 'Приоритет', 'Активна', 'Центр (lat)', 'Центр (lng)', 'Радиус (м)']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->name,
                                    $record->slug,
                                    $record->type,
                                    $record->priority,
                                    $record->is_active ? 'Да' : 'Нет',
                                    $record->center_latitude ?? '—',
                                    $record->center_longitude ?? '—',
                                    $record->radius_meters ?? '—',
                                ]);
                            }
                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
                Tables\Actions\BulkAction::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => true]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Зоны активированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Деактивировать')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => false]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Зоны деактивированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListGeoZones::route('/'),
            'create' => Pages\CreateGeoZone::route('/create'),
            'view' => Pages\ViewGeoZone::route('/{record}'),
            'edit' => Pages\EditGeoZone::route('/{record}/edit'),
        ];
    }
}
