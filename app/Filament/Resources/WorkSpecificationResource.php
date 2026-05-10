<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkSpecificationResource\Pages;
use App\Filament\Resources\WorkSpecificationResource\RelationManagers;
use App\Models\WorkSpecification;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class WorkSpecificationResource extends Resource
{
    protected static ?string $model = WorkSpecification::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'ТЗ и задачи';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?int $navigationSort = 104;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Section::make('Паспорт ТЗ')
                    ->description('Базовые сведения, которые видят все участники процесса.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Placeholder::make('public_id_display')
                                    ->label('Код ТЗ')
                                    ->content(fn (?WorkSpecification $record) => $record?->public_id ?? 'Будет присвоен автоматически после сохранения')
                                    ->columnSpan(1),
                                Placeholder::make('creator_display')
                                    ->label('Создатель')
                                    ->content(fn (?WorkSpecification $record) => $record?->creator?->name ?? auth()->user()?->name ?? '—')
                                    ->hint('После создания сохранится фактический автор')
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например, “Комплексное обслуживание зоны доставки #42”')
                            ->helperText('Краткое и понятное название задачи. Минимум 3 символа.')
                            ->columnSpan('full'),
                        RichEditor::make('description')
                            ->label('Описание')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'link',
                                'blockquote',
                                'undo',
                                'redo',
                            ])
                            ->placeholder('Расскажите о целях, ожидаемых результатах и критериях приёмки…')
                            ->helperText('Подробное описание задачи. Минимум 10 символов. Используйте форматирование для лучшей читаемости.')
                            ->extraInputAttributes(['class' => 'min-h-[220px]'])
                            ->columnSpan('full'),
                    ])
                    ->columns(2),

                Section::make('Привязки и ответственность')
                    ->description('Свяжите ТЗ с заказами/тикетами и назначьте исполнителей.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->label('Связанный заказ')
                                    ->helperText('Используется для автоматического связывания статусов и выписок.')
                                    ->placeholder('Выберите заказ или начните вводить номер')
                                    ->relationship('order', 'order_number', fn (Builder $query) => $query->orderByDesc('id'))
                                    ->searchable(['id', 'order_number'])
                                    ->getSearchResultsUsing(fn (string $search) => \App\Models\Order::query()
                                        ->where('id', 'like', "%{$search}%")
                                        ->orWhere('order_number', 'like', "%{$search}%")
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($order) => [$order->id => "{$order->order_number} (ID: {$order->id})"])
                                    )
                                    ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Order::find($value)?->order_number
                                    )
                                    ->preload()
                                    ->reactive()
                                    ->nullable()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('clear_order')
                                            ->icon('heroicon-o-x-circle')
                                            ->action(fn (callable $set) => $set('order_id', null))
                                    ),
                                Forms\Components\Select::make('ticket_id')
                                    ->label('Связанный тикет')
                                    ->helperText('Помогает поддержке видеть контекст задачи.')
                                    ->relationship('ticket', 'subject', fn (Builder $query) => $query->orderByDesc('created_at'))
                                    ->searchable(['subject', 'number'])
                                    ->getSearchResultsUsing(fn (string $search) => \App\Models\SupportTicket::where('subject', 'like', "%{$search}%")
                                        ->orWhere('number', 'like', "%{$search}%")
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($ticket) => [$ticket->id => "{$ticket->number}: {$ticket->subject}"])
                                    )
                                    ->getOptionLabelUsing(fn ($value): ?string => \App\Models\SupportTicket::find($value)?->subject
                                    )
                                    ->preload()
                                    ->reactive()
                                    ->nullable()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('clear_ticket')
                                            ->icon('heroicon-o-x-circle')
                                            ->action(fn (callable $set) => $set('ticket_id', null))
                                    ),
                            ]),
                        Forms\Components\Select::make('responsible_id')
                            ->label('Ответственный исполнитель')
                            ->relationship('responsible', 'name', function (Builder $query) {
                                return $query->whereHas('roles', function ($q) {
                                    $q->whereIn('name', ['courier', 'executor', 'eco_executor', 'roadside_assist', 'social_helper']);
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->helperText('Выберите того, кто будет отвечать за выполнение и отчётность. Можно оставить пустым и назначить позже.')
                            ->nullable()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('clear_responsible')
                                    ->icon('heroicon-o-x-circle')
                                    ->action(fn (callable $set) => $set('responsible_id', null))
                            ),
                    ])
                    ->columns(2),

                Section::make('Согласование и SLA')
                    ->description('Установите статус, приоритет и отметку подтверждения.')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'on_review' => 'На согласовании',
                                'approved' => 'Утверждено',
                                'changes_requested' => 'Требуются изменения',
                                'cancelled' => 'Отменено',
                                'archived' => 'Архивировано',
                            ])
                            ->required()
                            ->default('draft')
                            ->helperText('Статус виден на всех связанных досках.'),
                        Forms\Components\Select::make('priority')
                            ->label('Приоритет')
                            ->options([
                                'low' => 'Низкий',
                                'normal' => 'Обычный',
                                'high' => 'Высокий',
                                'urgent' => 'Срочный',
                            ])
                            ->required()
                            ->default('normal')
                            ->helperText('Приоритет влияет на сортировку списков и уведомления.'),
                        Forms\Components\DateTimePicker::make('worker_acknowledged_at')
                            ->label('Подтверждено исполнителем')
                            ->disabled()
                            ->helperText('Автозаполняется, когда исполнитель подтверждает принятие задачи.')
                            ->displayFormat('d.m.Y H:i')
                            ->visible(fn ($record) => $record !== null),
                    ])
                    ->columns(3),

                Section::make('Метаданные')
                    ->schema([
                        KeyValue::make('metadata')
                            ->label('Метаданные (JSON)')
                            ->helperText('Добавьте произвольные параметры: SLA, каналы связи, внешние ID. Эти данные сохраняются в формате JSON.')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->addButtonLabel('Добавить параметр')
                            ->reorderable()
                            ->default([])
                            ->columnSpan('full'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('public_id')
                    ->label('Код ТЗ')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->url(fn ($record) => $record->order_id
                        ? route('filament.resources.orders.view', $record->order_id)
                        : null)
                    ->openUrlInNewTab()
                    ->default('—'),
                TextColumn::make('responsible.name')
                    ->label('Ответственный')
                    ->default('—'),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Черновик',
                        'on_review' => 'На согласовании',
                        'approved' => 'Утверждено',
                        'changes_requested' => 'Требуются изменения',
                        'cancelled' => 'Отменено',
                        'archived' => 'Архивировано',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => fn ($state) => $state === 'draft',
                        'warning' => fn ($state) => $state === 'on_review',
                        'success' => fn ($state) => $state === 'approved',
                        'danger' => fn ($state) => $state === 'changes_requested',
                        'gray' => fn ($state) => in_array($state, ['cancelled', 'archived']),
                    ]),
                BadgeColumn::make('priority')
                    ->label('Приоритет')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Низкий',
                        'normal' => 'Обычный',
                        'high' => 'Высокий',
                        'urgent' => 'Срочный',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => fn ($state) => $state === 'low',
                        'success' => fn ($state) => $state === 'normal',
                        'warning' => fn ($state) => $state === 'high',
                        'danger' => fn ($state) => $state === 'urgent',
                    ]),
                TextColumn::make('worker_acknowledged_at')
                    ->label('Подтверждено')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '—';
                        }

                        try {
                            if ($state instanceof \Carbon\Carbon) {
                                return $state->format('d.m.Y H:i');
                            }

                            return \Carbon\Carbon::parse($state)->format('d.m.Y H:i');
                        } catch (\Exception $e) {
                            return '—';
                        }
                    }),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '—';
                        }

                        try {
                            if ($state instanceof \Carbon\Carbon) {
                                return $state->format('d.m.Y H:i');
                            }

                            return \Carbon\Carbon::parse($state)->format('d.m.Y H:i');
                        } catch (\Exception $e) {
                            return '—';
                        }
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'on_review' => 'На согласовании',
                        'approved' => 'Утверждено',
                        'changes_requested' => 'Требуются изменения',
                        'cancelled' => 'Отменено',
                        'archived' => 'Архивировано',
                    ]),
                SelectFilter::make('priority')
                    ->label('Приоритет')
                    ->options([
                        'low' => 'Низкий',
                        'normal' => 'Обычный',
                        'high' => 'Высокий',
                        'urgent' => 'Срочный',
                    ]),
                TernaryFilter::make('pending_ack')
                    ->label('Подтверждение worker\'ом')
                    ->placeholder('Все ТЗ')
                    ->trueLabel('Подтверждённые')
                    ->falseLabel('Не подтверждённые')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('worker_acknowledged_at'),
                        false: fn (Builder $query) => $query->whereNull('worker_acknowledged_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        // Отношение reviews пока не используется и модель не содержит метода reviews(),
        // поэтому временно отключаем RelationManager, чтобы не падала страница редактирования.
        return [
            // RelationManagers\ReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkSpecifications::route('/'),
            'create' => Pages\CreateWorkSpecification::route('/create'),
            'edit' => Pages\EditWorkSpecification::route('/{record}/edit'),
        ];
    }
}
