<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialCareOrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class SocialCareOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Social Care';

    protected static ?int $navigationSort = 706;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Соц. заказы';

    protected static ?string $modelLabel = 'Соц. заказ';

    protected static ?string $pluralModelLabel = 'Соц. заказы';

    protected static ?string $slug = 'social-care-orders';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where(function ($query) {
            $query->whereHas('careDetails')->orWhere('metadata->service_type', 'social_care_visit');
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Информация о визите')->schema([Forms\Components\Placeholder::make('client')->label('Клиент')->content(fn (?Order $record) => $record?->careDetails?->clientProfile?->full_name ?? '—'),                        Forms\Components\Placeholder::make('trusted_contact')->label('Доверенное лицо')->content(fn (?Order $record) => $record?->careDetails?->trustedContact?->full_name ?? '—'),                        Forms\Components\Placeholder::make('care_service')->label('Услуга')->content(fn (?Order $record) => $record?->careDetails?->careService?->name ?? '—'),                        Forms\Components\Placeholder::make('scheduled_start_at')->label('Начало')->content(fn (?Order $record) => optional($record?->careDetails?->scheduled_start_at)->format('d.m.Y H:i') ?? '—'),                        Forms\Components\Placeholder::make('scheduled_end_at')->label('Окончание')->content(fn (?Order $record) => optional($record?->careDetails?->scheduled_end_at)->format('d.m.Y H:i') ?? '—'),                        Forms\Components\Placeholder::make('assigned_helper')->label('Назначенный помощник')->content(fn (?Order $record) => $record?->careDetails?->assignedHelper?->display_name ?? '—'),                        Forms\Components\Placeholder::make('internal_notes')->label('Внутренние заметки')->content(fn (?Order $record) => $record?->careDetails?->internal_notes ?? '—')])->columns(2)->collapsible()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),                Tables\Columns\TextColumn::make('careDetails.clientProfile.full_name')->label('Клиент')->searchable()->sortable(),                Tables\Columns\TextColumn::make('careDetails.careService.name')->label('Тип услуги')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('careDetails.care_status')->label('Статус визита')->colors(['success' => 'COMPLETED',                        'warning' => 'IN_PROGRESS',                        'info' => 'SCHEDULED',                        'gray' => 'CANCELLED'])->default('SCHEDULED'),                Tables\Columns\TextColumn::make('careDetails.assignedHelper.display_name')->label('Помощник')->searchable()->sortable()->default('—'),                Tables\Columns\TextColumn::make('careDetails.scheduled_start_at')->label('Время визита')->dateTime()->sortable(),                Tables\Columns\BadgeColumn::make('status')->label('Статус заказа')->colors(['success' => 'completed',                        'warning' => 'in_progress',                        'info' => 'pending',                        'gray' => 'cancelled'])])->filters([Tables\Filters\SelectFilter::make('careDetails.care_status')->label('Статус визита')->options(['SCHEDULED' => 'Запланирован',                        'IN_PROGRESS' => 'В процессе',                        'COMPLETED' => 'Завершён',                        'CANCELLED' => 'Отменён']),                Tables\Filters\SelectFilter::make('assigned_helper_id')->label('Помощник')->options(fn () => \App\Models\User::query()->whereHas('roles', fn ($q) => $q->where('name', 'helper'))->pluck('name', 'id'))->query(function (Builder $query, array $data): Builder {
            $value = $data['value'] ?? null;

            return $query->when($value, fn (Builder $query, $value) => $query->whereHas('careDetails', fn (Builder $careQuery) => $careQuery->where('assigned_helper_id', $value)));
        })->searchable(),                Tables\Filters\SelectFilter::make('client_profile_id')->label('Клиент')->options(fn () => \App\Models\ClientProfile::query()->pluck('full_name', 'id'))->query(function (Builder $query, array $data): Builder {
            $value = $data['value'] ?? null;

            return $query->when($value, fn (Builder $query, $value) => $query->whereHas('careDetails', fn (Builder $careQuery) => $careQuery->where('client_profile_id', $value)));
        })->searchable()])->actions([Tables\Actions\ViewAction::make(),                Tables\Actions\ViewAction::make()])->bulkActions([Tables\Actions\BulkAction::make('export')->label('Экспорт (CSV)')->icon('heroicon-o-download')->action(function ($records) {
            $filename = 'social_care_orders_'.now()->format('Y-m-d_H-i-s').'.csv';
            $headers = ['Content-Type' => 'text/csv',                            'Content-Disposition' => "attachment; filename=\"{$filename}\""];
            $callback = function () use ($records) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'Номер заказа', 'Клиент', 'Тип услуги', 'Статус визита', 'Помощник', 'Время визита', 'Создано']);
                foreach ($records as $record) {
                    $careDetails = $record->careDetails;
                    fputcsv($file, [$record->id,                                    $record->order_number ?? '—',                                    $careDetails->clientProfile->full_name ?? '—',                                    $careDetails->careService->name ?? '—',                                    $careDetails->care_status ?? '—',                                    $careDetails->assignedHelper->display_name ?? 'Не назначен',                                    $careDetails->scheduled_start_at ? $careDetails->scheduled_start_at->format('Y-m-d H:i:s') : '—',                                    $record->created_at->format('Y-m-d H:i:s')]);
                }                            fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }),                Tables\Actions\BulkAction::make('change_status')->label('Изменить статус визита')->icon('heroicon-o-refresh')->form([Forms\Components\Select::make('care_status')->label('Новый статус')->options(['SCHEDULED' => 'Запланирован',                                'IN_PROGRESS' => 'В процессе',                                'COMPLETED' => 'Завершен',                                'CANCELLED' => 'Отменен'])->required()])->action(function ($records, array $data) {
            foreach ($records as $record) {
                if ($record->careDetails) {
                    $record->careDetails->update(['care_status' => $data['care_status']]);
                }
            }                        \Filament\Notifications\Notification::make()->title('Статус изменен')->body('Обновлено заказов: '.$records->count())->success()->send();
        })]);
    }

    public static function getRelations(): array
    {
        return [\App\Filament\Resources\OrderResource\RelationManagers\SubOrdersRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSocialCareOrders::route('/'),            'view' => Pages\ViewSocialCareOrder::route('/{record}')];
    }
}
