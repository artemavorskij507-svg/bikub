<?php

namespace App\Filament\Resources\Concerns;

use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasUltraProMaxFeatures
{
    /**
     * Get enhanced bulk actions with export and common operations
     */
    public static function getEnhancedBulkActions(): array
    {
        $modelName = class_basename(static::$model);
        $resourceName = Str::snake($modelName);

        return [
            Tables\Actions\BulkAction::make('export')
                ->label('Экспорт (CSV)')
                ->icon('heroicon-o-download')
                ->action(function ($records) use ($resourceName) {
                    $filename = $resourceName.'_'.now()->format('Y-m-d_H-i-s').'.csv';
                    $headers = [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    ];
                    $callback = function () use ($records) {
                        $file = fopen('php://output', 'w');
                        // Get first record to determine columns
                        $first = $records->first();
                        if ($first) {
                            $columns = array_keys($first->getAttributes());
                            fputcsv($file, $columns);
                            foreach ($records as $record) {
                                $row = [];
                                foreach ($columns as $column) {
                                    $value = $record->getAttribute($column);
                                    if ($value instanceof \Carbon\Carbon) {
                                        $row[] = $value->format('Y-m-d H:i:s');
                                    } else {
                                        $row[] = $value ?? '—';
                                    }
                                }
                                fputcsv($file, $row);
                            }
                        }
                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }),
        ];
    }

    /**
     * Get enhanced date range filter
     */
    public static function getDateRangeFilter(string $field = 'created_at', string $label = 'Дата создания'): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make($field)
            ->label($label)
            ->form([
                Forms\Components\DatePicker::make($field.'_from')
                    ->label('От'),
                Forms\Components\DatePicker::make($field.'_until')
                    ->label('До'),
            ])
            ->query(function (Builder $query, array $data) use ($field): Builder {
                return $query
                    ->when(
                        $data[$field.'_from'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate($field, '>=', $date),
                    )
                    ->when(
                        $data[$field.'_until'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate($field, '<=', $date),
                    );
            });
    }

    /**
     * Get enhanced numeric range filter
     */
    public static function getNumericRangeFilter(string $field, string $label): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make($field.'_range')
            ->label($label)
            ->form([
                Forms\Components\TextInput::make($field.'_from')
                    ->label('От')
                    ->numeric(),
                Forms\Components\TextInput::make($field.'_to')
                    ->label('До')
                    ->numeric(),
            ])
            ->query(function (Builder $query, array $data) use ($field): Builder {
                return $query
                    ->when(
                        $data[$field.'_from'] ?? null,
                        fn (Builder $query, $amount): Builder => $query->where($field, '>=', $amount),
                    )
                    ->when(
                        $data[$field.'_to'] ?? null,
                        fn (Builder $query, $amount): Builder => $query->where($field, '<=', $amount),
                    );
            });
    }
}
