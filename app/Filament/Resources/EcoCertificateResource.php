<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EcoCertificateResource\Pages;
use App\Models\EcoCertificate;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class EcoCertificateResource extends Resource
{
    protected static ?string $model = EcoCertificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Eco Disposal';

    protected static ?int $navigationSort = 303;

    protected static bool $shouldRegisterNavigation = true;

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Сертификат')->schema([Forms\Components\TextInput::make('certificate_uid')->label('UID')->disabled(),                        Forms\Components\TextInput::make('customer_name')->label('Клиент')->disabled(),                        Forms\Components\TextInput::make('order_number')->label('Заказ')->disabled()->formatStateUsing(fn ($state, EcoCertificate $record) => $record->order?->order_number ?? '—'),                        Forms\Components\TextInput::make('issued_at')->label('Выдан')->disabled()->formatStateUsing(fn ($state) => $state ? optional($state)->format('d.m.Y H:i') : '—')])->columns(2),                Forms\Components\Section::make('Экологический эффект')->schema([Forms\Components\TextInput::make('co2_saved_kg')->label('CO₂ сэкономлено, кг')->disabled()->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1, ',', ' ') : '—'),                        Forms\Components\TextInput::make('items_reused_count')->label('Повторно использовано предметов')->disabled()->formatStateUsing(fn ($state) => $state ?? '—')])->columns(2),                Forms\Components\Section::make('Детали')->schema([Forms\Components\KeyValue::make('summary_data')->label('Сводные данные')->keyLabel('Ключ')->valueLabel('Значение')->disabled()->columnSpanFull()])->columns(1)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('id')->label('#')->sortable()->toggleable(isToggledHiddenByDefault: true),                Tables\Columns\TextColumn::make('certificate_uid')->label('UID')->searchable()->copyable()->copyMessage('UID скопирован в буфер обмена')->copyMessageDuration(1500),                Tables\Columns\TextColumn::make('order.order_number')->label('Заказ')->searchable()->sortable()->description(fn (EcoCertificate $record) => $record->order?->user?->email),                Tables\Columns\TextColumn::make('customer_name')->label('Клиент')->searchable()->sortable(),                Tables\Columns\TextColumn::make('co2_saved_kg')->label('CO₂, кг')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1, ',', ' ') : '—')->sortable(),                Tables\Columns\TextColumn::make('items_reused_count')->label('Повторно использовано')->sortable(),                Tables\Columns\TextColumn::make('issued_at')->label('Выдан')->dateTime('d.m.Y H:i')->since()->sortable()])->filters([Tables\Filters\Filter::make('issued_at_recent')->label('За последние 30 дней')->query(fn (Builder $query) => $query->where('issued_at', '>=', now()->subDays(30)))])->actions([Tables\Actions\ViewAction::make(),                Tables\Actions\Action::make('download')->label('Скачать PDF')->icon('heroicon-o-download')->visible(fn (EcoCertificate $record) => ! empty($record->pdf_path))->url(fn (EcoCertificate $record) => \Illuminate\Support\Facades\Storage::url($record->pdf_path), true)])->bulkActions([Tables\Actions\DeleteBulkAction::make()->visible(false)])->defaultSort('issued_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEcoCertificates::route('/'),            'view' => Pages\ViewEcoCertificate::route('/{record}')];
    }
}
