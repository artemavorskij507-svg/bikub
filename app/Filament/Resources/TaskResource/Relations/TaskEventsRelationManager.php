<?php

namespace App\Filament\Resources\TaskResource\Relations;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;

class TaskEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Events';

    protected static ?string $recordTitleAttribute = 'to_status';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('from_status')
                ->label('From Status')
                ->disabled()
                ->default(fn ($livewire) => $livewire->ownerRecord->status ?? null),
            Forms\Components\Select::make('to_status')
                ->label('To Status')
                ->required()
                ->options([
                    'queued' => 'Queued',
                    'ready' => 'Ready',
                    'assigned' => 'Assigned',
                    'en_route' => 'En Route',
                    'arrived' => 'Arrived',
                    'in_progress' => 'In Progress',
                    'paused' => 'Paused',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'canceled' => 'Canceled',
                    'rescheduled' => 'Rescheduled',
                ])
                ->default(fn ($livewire) => $livewire->ownerRecord->status ?? null),
            Forms\Components\Textarea::make('reason')
                ->label('Reason')
                ->rows(3)
                ->placeholder('Optional reason for status change'),
            Forms\Components\KeyValue::make('payload')
                ->label('Payload (JSON)')
                ->keyLabel('Key')
                ->valueLabel('Value'),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-fill from_status with current task status if not set
        if (empty($data['from_status']) && $this->ownerRecord) {
            $data['from_status'] = $this->ownerRecord->status;
        }

        // Ensure task_id is set
        if (empty($data['task_id']) && $this->ownerRecord) {
            $data['task_id'] = $this->ownerRecord->id;
        }

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('from_status')
                    ->label('From')
                    ->colors([
                        'gray' => fn ($state) => filled($state),
                    ])
                    ->placeholder('—'),
                BadgeColumn::make('to_status')
                    ->label('To')
                    ->colors([
                        'secondary' => 'queued',
                        'info' => 'ready',
                        'warning' => 'assigned',
                        'primary' => 'en_route',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'canceled',
                    ]),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->wrap()
                    ->limit(50)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Event')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
