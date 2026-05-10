<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassifiedAdResource\Pages;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Carbon;

class ClassifiedAdResource extends Resource
{
    protected static ?string $model = ClassifiedAd::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Ads Moderation';

    protected static ?string $navigationGroup = 'Classifieds';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Overview')
                ->schema([
                    Infolists\Components\TextEntry::make('title')
                        ->label('Title')
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge(),
                    Infolists\Components\TextEntry::make('priceFormatted')
                        ->label('Price'),
                    Infolists\Components\TextEntry::make('category.name')
                        ->label('Category'),
                ])->columns(4),

            Infolists\Components\Section::make('Details')
                ->schema([
                    Infolists\Components\TextEntry::make('description')
                        ->label('Description')
                        ->html(),
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('User'),
                    Infolists\Components\TextEntry::make('address')
                        ->label('Address'),
                    Infolists\Components\TextEntry::make('moderation_reason')
                        ->label('Moderation reason')
                        ->color('danger')
                        ->visible(fn ($record) => ! empty($record->moderation_reason)),

                    Infolists\Components\ImageEntry::make('map_preview')
                        ->label('Location preview')
                        ->state(function ($record) {
                            if (! $record->location) {
                                return null;
                            }
                            $token = env('MAPBOX_TOKEN');
                            if (! $token) {
                                return null;
                            }

                            $lon = $record->location->longitude;
                            $lat = $record->location->latitude;

                            return "https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s-a+f00({$lon},{$lat})/{$lon},{$lat},13/600x300?access_token={$token}";
                        })
                        ->visible(fn ($record) => (bool) $record->location)
                        ->extraAttributes(['class' => 'rounded-xl overflow-hidden']),
                ])->columns(2),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Ad content')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Title')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(6),

                            Forms\Components\TextInput::make('address')
                                ->label('Address')
                                ->maxLength(255),
                        ]),

                    Forms\Components\Section::make('Media')
                        ->description('Upload photos and documents for this listing')
                        ->schema([
                            Forms\Components\SpatieMediaLibraryFileUpload::make('ads')
                                ->collection('ads')
                                ->label('Main Image')
                                ->image()
                                ->maxSize(5120)
                                ->singleFile(),

                            Forms\Components\SpatieMediaLibraryFileUpload::make('gallery')
                                ->collection('gallery')
                                ->label('Gallery Images')
                                ->image()
                                ->maxSize(5120)
                                ->multiple()
                                ->maxFiles(10)
                                ->columnSpanFull(),

                            Forms\Components\SpatieMediaLibraryFileUpload::make('documents')
                                ->collection('documents')
                                ->label('Documents (PDF, DOC, DOCX)')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                ])
                                ->maxSize(10240)
                                ->multiple()
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpan(2),

            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Status & price')
                        ->schema([
                            Forms\Components\TextInput::make('price_value')
                                ->label('Price (in øre, cents)')
                                ->numeric()
                                ->minValue(0)
                                ->helperText('Храним цену в минимальных единицах валюты (например, 19900 = 199 NOK).'),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'draft' => 'Draft',
                                    'moderation' => 'Moderation',
                                    'published' => 'Published',
                                    'sold' => 'Sold',
                                    'expired' => 'Expired',
                                ])
                                ->required(),

                            Forms\Components\Textarea::make('moderation_reason')
                                ->label('Moderation / reject reason')
                                ->rows(3),
                        ]),
                ])
                ->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('gallery')
                    ->label('Image')
                    ->getStateUsing(function (ClassifiedAd $record) {
                        return $record->getFirstMediaUrl('ads') ?: $record->getFirstMediaUrl('gallery');
                    })
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_value')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2, ',', ' ').' NOK' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'moderation',
                        'danger' => 'sold',
                        'secondary' => ['draft', 'expired'],
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'moderation' => 'Moderation',
                        'published' => 'Published',
                        'sold' => 'Sold',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ClassifiedAd $record) => $record->status === 'moderation')
                    ->action(function (ClassifiedAd $record) {
                        $record->update([
                            'status' => 'published',
                            'published_at' => Carbon::now(),
                            'moderation_reason' => null,
                        ]);

                        Notification::make()
                            ->title('Ad approved')
                            ->body("Ad '{$record->title}' is now live.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ClassifiedAd $record) => in_array($record->status, ['moderation', 'published']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (ClassifiedAd $record, array $data) {
                        $record->update([
                            'status' => 'draft',
                            'moderation_reason' => $data['reason'],
                        ]);

                        Notification::make()
                            ->title('Ad rejected')
                            ->body("Ad '{$record->title}' returned to draft.")
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('approve_selected')
                    ->label('Approve selected')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        /** @var \Illuminate\Database\Eloquent\Collection $records */
                        $records->each(function (ClassifiedAd $record) {
                            if ($record->status === 'moderation') {
                                $record->update([
                                    'status' => 'published',
                                    'published_at' => Carbon::now(),
                                ]);
                            }
                        });
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassifiedAds::route('/'),
            'edit' => Pages\EditClassifiedAd::route('/{record}/edit'),
            'view' => Pages\ViewClassifiedAd::route('/{record}'),
        ];
    }
}
