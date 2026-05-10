<?php

namespace App\Filament\Resources;

use App\Models\FeatureFlag;
use Filament\Forms;
use Filament\Forms\Components as F;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns as C;
use Illuminate\Database\Eloquent\Builder;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Feature Flags';

    protected static ?int $navigationSort = 107;

    public static function form(Form $form): Form
    {
        return $form->schema([
            F\TextInput::make('key')->required()->unique(ignoreRecord: true),
            F\TextInput::make('name')->required(),
            F\Textarea::make('description')->rows(2),
            F\Toggle::make('is_active')->label('Active'),
            F\Toggle::make('default_on')->label('Default ON'),
            F\TextInput::make('rollout_percent')->numeric()->minValue(0)->maxValue(100)->default(100),
            F\DateTimePicker::make('starts_at'),
            F\DateTimePicker::make('ends_at'),
            F\KeyValue::make('rules')->label('Rules (JSON)')->helperText('веса/порог для алгоритмов'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            C\TextColumn::make('key')->searchable()->copyable(),
            C\TextColumn::make('name')->searchable(),
            C\IconColumn::make('is_active')->boolean()->label('Active'),
            C\IconColumn::make('default_on')->boolean()->label('Def'),
            C\TextColumn::make('rollout_percent')->suffix('%')->label('Rollout'),
            C\TextColumn::make('starts_at')->dateTime('MMM d, HH:mm')->toggleable(),
            C\TextColumn::make('ends_at')->dateTime('MMM d, HH:mm')->toggleable(),
            C\TextColumn::make('updated_at')->since()->label('Updated'),
        ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен')
                    ->placeholder('Все')
                    ->trueLabel('Активен')
                    ->falseLabel('Неактивен'),
                Tables\Filters\TernaryFilter::make('default_on')
                    ->label('По умолчанию ON')
                    ->placeholder('Все'),
                Tables\Filters\Filter::make('rollout_percent')
                    ->label('Rollout %')
                    ->form([
                        Forms\Components\TextInput::make('rollout_from')
                            ->label('От')
                            ->numeric(),
                        Forms\Components\TextInput::make('rollout_to')
                            ->label('До')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['rollout_from'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('rollout_percent', '>=', $value),
                            )
                            ->when(
                                $data['rollout_to'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('rollout_percent', '<=', $value),
                            );
                    }),
                Tables\Filters\Filter::make('valid_now')
                    ->label('Действует сейчас')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                        })
                        ->where(function ($q) {
                            $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                        })
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (FeatureFlag $record) => $record->is_active ? 'Деактивировать' : 'Активировать')
                    ->icon(fn (FeatureFlag $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (FeatureFlag $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (FeatureFlag $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->is_active ? 'Feature Flag активирован' : 'Feature Flag деактивирован')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'feature_flags_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Key', 'Name', 'Active', 'Default ON', 'Rollout %', 'Starts At', 'Ends At', 'Updated']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->key,
                                    $record->name,
                                    $record->is_active ? 'Да' : 'Нет',
                                    $record->default_on ? 'Да' : 'Нет',
                                    $record->rollout_percent,
                                    $record->starts_at ? $record->starts_at->format('Y-m-d H:i:s') : '—',
                                    $record->ends_at ? $record->ends_at->format('Y-m-d H:i:s') : '—',
                                    $record->updated_at->format('Y-m-d H:i:s'),
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
                            ->title('Feature Flags активированы')
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
                            ->title('Feature Flags деактивированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('previewAs')
                    ->label('Проверить для пользователя')
                    ->icon('heroicon-o-eye')
                    ->form([
                        F\TextInput::make('userId'),
                        F\TextInput::make('role'),
                        F\TextInput::make('orgId'),
                        F\TextInput::make('zoneId'),
                        F\TextInput::make('serviceTypeId'),
                        F\TextInput::make('flagKey')->required(),
                    ])->action(function (array $data, $livewire) {
                        $enabled = app(\App\Services\FeatureFlags\FeatureFlagger::class)
                            ->enabled($data['flagKey'], new \App\Services\FeatureFlags\Context(
                                $data['orgId'] ?? null,
                                $data['zoneId'] ?? null,
                                $data['serviceTypeId'] ?? null,
                                $data['userId'] ?? null,
                                $data['role'] ?? null
                            ));
                        $livewire->notify('success', 'Результат: '.($enabled ? 'ENABLED' : 'DISABLED'));
                    }),
                Tables\Actions\Action::make('clearCache')
                    ->label('Очистить кеш FF')->color('warning')
                    ->action(fn () => \App\Services\FeatureFlags\FeatureFlagger::clearCache()),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => FeatureFlagResource\Pages\ListFeatureFlags::route('/'),
            'create' => FeatureFlagResource\Pages\CreateFeatureFlag::route('/create'),
            'view' => FeatureFlagResource\Pages\ViewFeatureFlag::route('/{record}'),
            'edit' => FeatureFlagResource\Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}
