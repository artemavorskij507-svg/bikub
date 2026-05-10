<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnalyticsResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsResource extends Resource
{
    protected static ?string $model = Order::class; // Use Order as base model for analytics

    protected static ?string $slug = 'analytics-orders';

    protected static ?string $navigationIcon = 'heroicon-o-table';

    protected static ?string $navigationLabel = 'Заказы (Аналитика)';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?int $navigationSort = 108;

    public static function form(Form $form): Form
    {
        // Form is not used for creating orders in AnalyticsResource
        // This resource is for viewing/filtering only
        // Use OrderResource for creating new orders
        return $form
            ->schema([
                // Empty schema - form should not be used for creation
            ]);
    }

    public static function canCreate(): bool
    {
        return false; // Disable creation in AnalyticsResource
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Номер заказа')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Order $record): string => $record->user->email ?? '—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => ['pending'],
                        'info' => ['confirmed'],
                        'primary' => ['in_progress'],
                        'success' => ['completed'],
                        'danger' => ['cancelled'],
                        'secondary',
                    ]),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Платеж')
                    ->colors([
                        'warning' => ['pending'],
                        'success' => ['paid'],
                        'danger' => ['failed'],
                        'secondary' => ['cancelled'],
                        'secondary',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->sortable()
                    ->formatStateUsing(fn ($state, ?Order $record): string => $record && $record->total_amount !== null
                        ? number_format($record->total_amount, 2, ',', ' ').' '.($record->currency ?? 'NOK')
                        : '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Завершен')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Тип услуги')
                    ->formatStateUsing(function ($state, ?Order $record): string {
                        $meta = $record?->metadata;
                        if (is_array($meta) && isset($meta['service_type'])) {
                            return (string) $meta['service_type'];
                        }

                        return $state ?: '—';
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус заказа')
                    ->multiple()
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтвержден',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Статус платежа')
                    ->multiple()
                    ->options([
                        'pending' => 'Ожидает',
                        'paid' => 'Оплачен',
                        'failed' => 'Неудачный',
                        'cancelled' => 'Отменен',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->label('Дата создания')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('completed_at')
                    ->label('Дата завершения')
                    ->form([
                        Forms\Components\DatePicker::make('completed_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('completed_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['completed_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('completed_at', '>=', $date),
                            )
                            ->when(
                                $data['completed_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('completed_at', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('amount_range')
                    ->label('Сумма заказа')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('От')
                            ->numeric(),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('До')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'] ?? null,
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'] ?? null,
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Order $record): string => route('filament.resources.orders.view', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_csv')
                    ->label('Экспорт CSV')
                    ->icon('heroicon-o-arrow-down')
                    ->action(function ($records) {
                        $csvData = [];
                        $csvData[] = [
                            'Order Number',
                            'Customer Name',
                            'Customer Email',
                            'Status',
                            'Payment Status',
                            'Total Amount',
                            'Currency',
                            'Created At',
                            'Completed At',
                        ];

                        foreach ($records as $order) {
                            $csvData[] = [
                                $order->order_number,
                                $order->user->name ?? 'N/A',
                                $order->user->email ?? 'N/A',
                                $order->status,
                                $order->payment_status,
                                $order->total_amount,
                                $order->currency,
                                $order->created_at->format('Y-m-d H:i:s'),
                                $order->completed_at?->format('Y-m-d H:i:s') ?? 'N/A',
                            ];
                        }

                        $filename = 'orders_export_'.now()->format('Y-m-d_H-i-s').'.csv';

                        return response()->streamDownload(function () use ($csvData) {
                            $file = fopen('php://output', 'w');
                            foreach ($csvData as $row) {
                                fputcsv($file, $row);
                            }
                            fclose($file);
                        }, $filename, [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAnalytics::route('/'),
            // Removed 'create' page - AnalyticsResource is for viewing/filtering orders only
            // Use OrderResource for creating new orders
        ];
    }
}
