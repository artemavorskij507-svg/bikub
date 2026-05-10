<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns as C;

class WebhookLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'webhookLogs';

    protected static ?string $title = 'Webhook Logs';

    public static function table(Table $table): Table
    {
        return $table->columns([
            C\TextColumn::make('created_at')->since()->label('Time'),
            C\BadgeColumn::make('event_type')->label('Event'),
            C\BadgeColumn::make('status')->colors([
                'success' => 'ok',
                'danger' => 'failed',
                'warning' => 'pending',
                'gray' => 'abandoned',
            ]),
            C\TextColumn::make('response_status')->label('HTTP'),
            C\TextColumn::make('attempt')->label('Try'),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options([
                'pending' => 'pending', 'ok' => 'ok', 'failed' => 'failed', 'abandoned' => 'abandoned',
            ]),
            Tables\Filters\SelectFilter::make('event_type')->label('Event'),
        ])->actions([
            Tables\Actions\Action::make('retry')->label('Retry now')
                ->visible(fn ($r) => $r->status === 'failed')
                ->action(fn ($r) => dispatch(new \App\Jobs\Partners\DispatchPartnerWebhookJob($r->id))->onQueue('webhooks')),
        ]);
    }
}
