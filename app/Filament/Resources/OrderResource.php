<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\HandymanAssignmentsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\SubOrdersRelationManager;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Orders / Заказы';

    protected static ?string $navigationGroup = 'Operations / Операции';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Order / Заказ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Orders / Заказы';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Summary')
                ->description('Key business context of the order.')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('order_number')
                            ->label('Order number')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                        TextInput::make('payment_status')
                            ->label('Payment status')
                            ->disabled(),
                        TextInput::make('scenario_key')
                            ->label('Scenario key')
                            ->disabled()
                            ->visible(fn (): bool => Schema::hasColumn('orders', 'scenario_key')),
                        TextInput::make('service_type')
                            ->label('Service type')
                            ->disabled(),
                        TextInput::make('priority')
                            ->label('Priority')
                            ->disabled(),
                        DateTimePicker::make('created_at')
                            ->label('Created at')
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('scheduled_at')
                            ->label('Time slot')
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('sla_deadline')
                            ->label('SLA deadline')
                            ->disabled()
                            ->visible(fn (): bool => Schema::hasColumn('orders', 'sla_deadline')),
                    ]),
                ]),
            Section::make('Customer')
                ->schema([
                    Select::make('user_id')
                        ->label('Client')
                        ->relationship('user', 'email')
                        ->searchable()
                        ->disabled(),
                    TextInput::make('metadata.guest_name')
                        ->label('Guest name')
                        ->disabled(),
                    TextInput::make('metadata.guest_email')
                        ->label('Guest email')
                        ->disabled(),
                    TextInput::make('metadata.guest_phone')
                        ->label('Guest phone')
                        ->disabled(),
                ])
                ->columns(2),
            Section::make('Addresses and SLA')
                ->schema([
                    Textarea::make('metadata.pickup_address')
                        ->label('Pickup address')
                        ->rows(2)
                        ->disabled(),
                    Textarea::make('metadata.dropoff_address')
                        ->label('Delivery/dropoff address')
                        ->rows(2)
                        ->disabled(),
                    TextInput::make('metadata.pickup_lat')
                        ->label('Pickup lat')
                        ->disabled(),
                    TextInput::make('metadata.pickup_lng')
                        ->label('Pickup lng')
                        ->disabled(),
                    TextInput::make('metadata.dropoff_lat')
                        ->label('Dropoff lat')
                        ->disabled(),
                    TextInput::make('metadata.dropoff_lng')
                        ->label('Dropoff lng')
                        ->disabled(),
                    DateTimePicker::make('metadata.time_slot_start')
                        ->label('Slot start')
                        ->disabled(),
                    DateTimePicker::make('metadata.time_slot_end')
                        ->label('Slot end')
                        ->disabled(),
                ])
                ->columns(2),
            Section::make('Assignment')
                ->schema([
                    Select::make('assigned_to')
                        ->label('Assigned worker')
                        ->relationship('assignedUser', 'name')
                        ->searchable()
                        ->disabled(),
                    TextInput::make('metadata.assignment_status')
                        ->label('Assignment status')
                        ->disabled(),
                    TextInput::make('roadside_partner_id')
                        ->label('Partner id')
                        ->disabled()
                        ->visible(fn (): bool => Schema::hasColumn('orders', 'roadside_partner_id')),
                ])
                ->columns(3),
            Section::make('Payment')
                ->schema([
                    TextInput::make('estimated_total')
                        ->label('Estimated price')
                        ->disabled(),
                    TextInput::make('final_price')
                        ->label('Final price')
                        ->disabled(),
                    TextInput::make('currency')
                        ->label('Currency')
                        ->disabled(),
                    TextInput::make('payment_status')
                        ->label('Payment status')
                        ->disabled(),
                    TextInput::make('payment_method')
                        ->label('Payment method')
                        ->disabled(),
                    TextInput::make('payment_intent_id')
                        ->label('Payment reference')
                        ->disabled(),
                ])
                ->columns(3),
            Section::make('Notes')
                ->schema([
                    Textarea::make('notes')
                        ->label('Customer note')
                        ->rows(3)
                        ->disabled(),
                    Textarea::make('metadata.internal_note')
                        ->label('Internal note')
                        ->rows(3)
                        ->disabled(),
                ])
                ->columns(2),
            Section::make('Scenario metadata')
                ->schema([
                    Placeholder::make('metadata_pretty')
                        ->label('Metadata')
                        ->content(function (?Order $record): string {
                            $metadata = $record?->metadata;
                            if (! is_array($metadata) || $metadata === []) {
                                return 'No metadata';
                            }

                            return json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'No metadata';
                        }),
                ]),
            Section::make('Events / History')
                ->schema([
                    Placeholder::make('events_preview')
                        ->label('Order events')
                        ->content(function (?Order $record): string {
                            if (! Schema::hasTable('order_events')) {
                                return 'Order events table pending migration';
                            }

                            if (! $record) {
                                return 'No order loaded';
                            }

                            $events = $record->events()->limit(20)->get(['event_type', 'from_status', 'to_status', 'created_at']);
                            if ($events->isEmpty()) {
                                return 'No events yet';
                            }

                            return $events->map(function ($event): string {
                                $timestamp = optional($event->created_at)->format('Y-m-d H:i:s');
                                return trim(($timestamp ? "[$timestamp] " : '').$event->event_type.' '.$event->from_status.' -> '.$event->to_status);
                            })->implode(PHP_EOL);
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'created',
                        'warning' => fn ($state): bool => in_array($state, ['payment_pending', 'waiting_dispatch', 'assigned', 'worker_accepted'], true),
                        'info' => fn ($state): bool => in_array($state, ['worker_en_route', 'at_pickup', 'picked_up', 'in_progress', 'arrived'], true),
                        'success' => fn ($state): bool => in_array($state, ['completed', 'client_confirmed', 'paid_out'], true),
                        'danger' => fn ($state): bool => in_array($state, ['cancelled', 'disputed', 'failed'], true),
                    ])
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => fn ($state): bool => in_array($state, ['reserved', 'authorized'], true),
                        'success' => 'captured',
                        'danger' => fn ($state): bool => in_array($state, ['failed', 'cancelled', 'refunded', 'partially_refunded'], true),
                    ]),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Worker')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('scenario_key')
                    ->label('Scenario')
                    ->toggleable()
                    ->visible(fn (): bool => Schema::hasColumn('orders', 'scenario_key')),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'created' => 'Created',
                        'payment_pending' => 'Payment pending',
                        'confirmed' => 'Confirmed',
                        'waiting_dispatch' => 'Waiting dispatch',
                        'assigned' => 'Assigned',
                        'in_progress' => 'In progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'disputed' => 'Disputed',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('service_type')->label('Service type'),
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
            SubOrdersRelationManager::class,
            HandymanAssignmentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'assignedUser']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
