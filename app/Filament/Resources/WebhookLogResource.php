<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookLogResource\Pages;
use App\Jobs\ProcessWebhook;
use App\Models\WebhookLog;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;

class WebhookLogResource extends Resource
{
    protected static ?string $model = WebhookLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Webhook Center';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('provider')
                        ->label('Provider')
                        ->disabled(),
                    Forms\Components\TextInput::make('event_type')
                        ->label('Event Type')
                        ->disabled(),
                    Forms\Components\TextInput::make('external_id')
                        ->label('External ID')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Status')
                        ->disabled(),
                    Forms\Components\TextInput::make('request_id')
                        ->label('Request ID')
                        ->disabled(),

                    // Correlation section
                    Forms\Components\Section::make('📊 Correlation Info')
                        ->schema([
                            Forms\Components\TextInput::make('order_id')
                                ->formatStateUsing(fn ($state) => $state ? "Order #{$state}" : '—')
                                ->label('Linked Order')
                                ->disabled(),
                            Forms\Components\TextInput::make('payment_id')
                                ->formatStateUsing(fn ($state) => $state ? "Payment #{$state}" : '—')
                                ->label('Linked Payment')
                                ->disabled(),
                            Forms\Components\Textarea::make('correlation_metadata')
                                ->default(fn ($record) => $record->metadata ? json_encode($record->metadata['correlation'] ?? [], JSON_PRETTY_PRINT) : '—')
                                ->label('Correlation Details')
                                ->disabled()
                                ->rows(5),
                        ]),

                    Forms\Components\Textarea::make('payload')
                        ->default(fn ($record) => json_encode($record->payload, JSON_PRETTY_PRINT))
                        ->label('Payload')
                        ->disabled()
                        ->rows(10),
                    Forms\Components\Textarea::make('error_message')
                        ->label('Error')
                        ->disabled()
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->dateTime()->label('Received At'),
            TextColumn::make('provider')->label('Provider'),
            TextColumn::make('event_type')->label('Event'),
            BadgeColumn::make('status')->colors(['success' => 'processed', 'warning' => 'received', 'danger' => 'failed']),
            TextColumn::make('external_id')->label('External ID')->limit(30),
            TextColumn::make('request_id')->label('Request ID')->limit(36),
            // Show linked status
            BadgeColumn::make('linked')
                ->getStateUsing(fn ($record) => $record->order_id || $record->payment_id ? 'Linked' : 'Unlinked')
                ->colors(['success' => 'Linked', 'warning' => 'Unlinked'])
                ->label('Linked'),
        ])->filters([
            Tables\Filters\SelectFilter::make('provider')->options([
                'stripe' => 'stripe', 'n8n' => 'n8n', 'sms' => 'sms', 'internal' => 'internal',
            ]),
            Tables\Filters\SelectFilter::make('status')->options([
                'received' => 'received', 'processed' => 'processed', 'failed' => 'failed',
            ]),
            Tables\Filters\Filter::make('date')->form([
                Forms\Components\DatePicker::make('from'),
                Forms\Components\DatePicker::make('to'),
            ])->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                if ($data['from']) {
                    $query->where('created_at', '>=', $data['from']);
                }
                if ($data['to']) {
                    $query->where('created_at', '<=', $data['to']);
                }

                return $query;
            }),
        ])->actions([
            // Link to Order if exists
            Action::make('view_order')
                ->label('View Order')
                ->icon('heroicon-o-external-link')
                ->url(fn ($record) => $record->order_id ? route('filament.resources.orders.edit', $record->order_id) : '#')
                ->openUrlInNewTab()
                ->visible(fn ($record) => (bool) $record->order_id),

            // Link to Payment if exists
            Action::make('view_payment')
                ->label('View Payment')
                ->icon('heroicon-o-external-link')
                ->url(fn ($record) => $record->payment_id ? route('filament.resources.payments.edit', $record->payment_id) : '#')
                ->openUrlInNewTab()
                ->visible(fn ($record) => (bool) $record->payment_id),

            // Retry action
            Action::make('retry')
                ->label('Retry')
                ->requiresConfirmation()
                ->action(function ($record) {
                    // reset status and dispatch job
                    $record->status = 'received';
                    $record->error_message = null;
                    $record->processed_at = null;
                    $record->attempt = ($record->attempt ?? 0) + 1;
                    $record->save();

                    ProcessWebhook::dispatch($record->id)->onQueue('webhooks');

                    // audit
                    app(\App\Services\AuditLogger::class)->log('webhook_retry', WebhookLog::class, $record->id, null, ['attempt' => $record->attempt], request());
                })
                ->visible(fn (WebhookLog $record): bool => $record->status === 'failed'),

            Tables\Actions\ViewAction::make(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookLogs::route('/'),
            'view' => Pages\ViewWebhookLog::route('/{record}'),
        ];
    }
}
