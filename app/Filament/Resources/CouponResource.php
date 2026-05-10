<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 301;

    protected static ?string $navigationLabel = 'Coupons';

    protected static ?string $modelLabel = 'Coupon';

    protected static ?string $pluralModelLabel = 'Coupons';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->columnSpan(1),

                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'percent' => 'Percentage (%)',
                                'fixed' => 'Fixed Amount (CHF)',
                                'free_delivery' => 'Free Delivery',
                                'first_order' => 'First Order Discount',
                            ])
                            ->required()
                            ->reactive()
                            ->columnSpan(1),

                        TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->columnSpan(1)
                            ->helperText(fn ($get) => match ($get('type')) {
                                'percent' => 'Percentage value (e.g., 10 for 10%)',
                                'fixed', 'first_order' => 'Amount in CHF',
                                default => 'Value',
                            }),

                        TextInput::make('max_uses')
                            ->label('Maximum Uses')
                            ->numeric()
                            ->nullable()
                            ->helperText('Leave empty for unlimited'),

                        TextInput::make('minimum_order_amount')
                            ->label('Minimum Order Amount (CHF)')
                            ->numeric()
                            ->step(0.01)
                            ->nullable(),

                        DateTimePicker::make('valid_from')
                            ->label('Valid From')
                            ->required(),

                        DateTimePicker::make('valid_to')
                            ->label('Valid To')
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Card::make()
                    ->schema([
                        Textarea::make('meta.description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        TagsInput::make('applicable_categories')
                            ->label('Applicable Categories')
                            ->helperText('Leave empty to apply to all categories')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percent' => 'Процент',
                        'fixed' => 'Фиксированная',
                        'free_delivery' => 'Бесплатная доставка',
                        'first_order' => 'Первый заказ',
                        default => $state,
                    }),

                TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(fn ($record) => match ($record->type) {
                        'percent' => $record->value.'%',
                        'free_delivery' => 'FREE',
                        'first_order', 'fixed' => 'CHF '.number_format($record->value, 2),
                        default => $record->value,
                    }),

                TextColumn::make('used')
                    ->label('Used / Max')
                    ->formatStateUsing(fn ($record) => $record->used.' / '.($record->max_uses ?? '∞'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('valid_to')
                    ->label('Expires')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'percent' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                        'free_delivery' => 'Free Delivery',
                        'first_order' => 'First Order',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->placeholder('All Coupons'),
            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
