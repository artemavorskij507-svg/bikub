<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasUltraProMaxFeatures;
use App\Filament\Resources\SupportTicketResource\Pages;
use App\Filament\Resources\SupportTicketResource\RelationManagers;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SupportTicketResource extends Resource
{
    use HasUltraProMaxFeatures;

    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-alt';

    protected static ?string $navigationLabel = 'Support Tickets';

    protected static ?string $navigationGroup = 'Коммуникации';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ticket Details')
                    ->schema([
                        Placeholder::make('number')
                            ->label('Ticket Number')
                            ->content(fn (?SupportTicket $record) => $record?->number ?? '-'),
                        Placeholder::make('client')
                            ->label('Client')
                            ->content(function (?SupportTicket $record) {
                                return $record?->user
                                    ? "{$record->user->name} ({$record->user->email})"
                                    : '-';
                            }),
                        Forms\Components\TextInput::make('subject')
                            ->label('Subject')
                            ->disabled()
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->rows(6)
                            ->disabled()
                            ->helperText(function (?SupportTicket $record) {
                                return $record
                                    ? 'Length: '.mb_strlen($record->message ?? '').' chars'
                                    : null;
                            })
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Section::make('Context')
                    ->schema([
                        Forms\Components\TextInput::make('role_context')
                            ->label('User Role')
                            ->disabled(),
                        Forms\Components\TextInput::make('channel')
                            ->label('Channel')
                            ->disabled(),
                        Forms\Components\TextInput::make('source')
                            ->label('Source')
                            ->disabled(),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->disableEditingKeys()
                            ->disableEditingValues()
                            ->columnSpan('full'),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('updated_at')
                            ->label('Updated At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Resolved At')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('Ticket Management')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Open',
                                'in_progress' => 'In Progress',
                                'resolved' => 'Resolved',
                                'closed' => 'Closed',
                            ])
                            ->required()
                            ->reactive()
                            ->helperText('Changing status auto-updates resolution fields')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (in_array($state, ['resolved', 'closed'], true)) {
                                    $set('resolved_at', now());
                                    if (Schema::hasColumn('support_tickets', 'resolved_by')) {
                                        $set('resolved_by', auth()->id());
                                    }
                                } else {
                                    $set('resolved_at', null);
                                    if (Schema::hasColumn('support_tickets', 'resolved_by')) {
                                        $set('resolved_by', null);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Admin Note')
                            ->helperText('Internal note for support operators')
                            ->rows(4)
                            ->columnSpan('full'),
                        Placeholder::make('resolver')
                            ->label('Resolved By')
                            ->content(function (?SupportTicket $record) {
                                return $record?->resolver
                                    ? "{$record->resolver->name} ({$record->resolver->email})"
                                    : '-';
                            }),
                        Placeholder::make('time_info')
                            ->label('Timing')
                            ->content(function (?SupportTicket $record) {
                                if (! $record) {
                                    return '-';
                                }

                                $created = $record->created_at?->format('d.m.Y H:i') ?? '-';
                                $resolved = $record->resolved_at
                                    ? $record->resolved_at->format('d.m.Y H:i')
                                    : 'Not resolved';

                                $duration = $record->resolved_at
                                    ? $record->created_at->diffForHumans($record->resolved_at, true)
                                    : $record->created_at->diffForHumans(now(), true);

                                return "Created: {$created}\nResolved: {$resolved}\nDuration: {$duration}";
                            })
                            ->columnSpan('full'),
                        Forms\Components\Hidden::make('resolved_by')
                            ->visible(fn () => Schema::hasColumn('support_tickets', 'resolved_by')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Copied'),
                TextColumn::make('subject')
                    ->label('Subject')
                    ->description(fn (SupportTicket $record) => Str::limit($record->message ?? '', 60))
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Client')
                    ->description(fn (SupportTicket $record) => $record->user?->email ?? '-')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('role_context')
                    ->label('Role')
                    ->colors(['info' => fn (?string $state) => filled($state)])
                    ->sortable()
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                        default => (string) $state,
                    })
                    ->colors([
                        'warning' => 'open',
                        'info' => 'in_progress',
                        'success' => 'resolved',
                        'gray' => 'closed',
                    ])
                    ->sortable(),
                BadgeColumn::make('priority')
                    ->label('Priority')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                        default => (string) $state,
                    })
                    ->colors([
                        'gray' => 'low',
                        'primary' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->sortable(),
                BadgeColumn::make('channel')
                    ->label('Channel')
                    ->colors([
                        'success' => 'lk',
                        'info' => 'email',
                        'warning' => 'phone',
                        'gray' => fn ($state) => ! in_array($state, ['lk', 'email', 'phone'], true),
                    ])
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('resolved_at')
                    ->label('Resolved')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('admin_note')
                    ->label('Note')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ])
                    ->multiple(),
                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->multiple(),
                SelectFilter::make('channel')
                    ->label('Channel')
                    ->options([
                        'lk' => 'Personal Account',
                        'email' => 'Email',
                        'phone' => 'Phone',
                    ])
                    ->multiple(),
                SelectFilter::make('role_context')
                    ->label('User Role')
                    ->options([
                        'client' => 'Client',
                        'worker' => 'Worker',
                        'dispatcher' => 'Dispatcher',
                        'partner' => 'Partner',
                    ])
                    ->multiple(),
                Filter::make('unresolved')
                    ->label('Unresolved only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotIn('status', ['resolved', 'closed'])),
                Filter::make('created_range')
                    ->label('Created Date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('From'),
                        Forms\Components\DatePicker::make('created_until')->label('To'),
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markResolved')
                    ->label('Mark as Resolved')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicket $record) => ! in_array($record->status, ['resolved', 'closed'], true))
                    ->action(function (SupportTicket $record) {
                        $record->update(static::buildResolvePayload());

                        Notification::make()
                            ->title('Ticket resolved')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                ...static::getEnhancedBulkActions(),
                Tables\Actions\BulkAction::make('resolve')
                    ->label('Resolve selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(static::buildResolvePayload());
                        }

                        Notification::make()
                            ->title('Tickets updated')
                            ->body('Updated: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('change_priority')
                    ->label('Change priority')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('priority')
                            ->label('New priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        foreach ($records as $record) {
                            $record->update(['priority' => $data['priority']]);
                        }

                        Notification::make()
                            ->title('Priority updated')
                            ->body('Updated: '.$records->count())
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function buildResolvePayload(): array
    {
        $payload = [
            'status' => 'resolved',
            'resolved_at' => now(),
        ];

        if (Schema::hasColumn('support_tickets', 'resolved_by')) {
            $payload['resolved_by'] = auth()->id();
        }

        return $payload;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
