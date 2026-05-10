<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdCategoryResource\Pages;
use App\Modules\Classifieds\Models\AdCategory;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Schema;

class AdCategoryResource extends Resource
{
    protected static ?string $model = AdCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationLabel = 'Ad Categories';

    protected static ?string $navigationGroup = 'Classifieds';

    public static function form(Form $form): Form
    {
        $hasFeatureTables = Schema::hasTable('ad_features') && Schema::hasTable('category_feature');

        $schema = [
            Forms\Components\Section::make('Category')
                ->description('Base category information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Category name')
                        ->required()
                        ->maxLength(100)
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            if (! filled($state)) {
                                return;
                            }

                            $set('slug', \Illuminate\Support\Str::slug($state));
                        })
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(2),

                    Forms\Components\Select::make('parent_id')
                        ->label('Parent category')
                        ->relationship('parent', 'name', function ($query) {
                            return $query->where('is_active', true);
                        })
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->columnSpan(2),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpan(2),
                ])
                ->columns(2),

            Forms\Components\Section::make('SEO')
                ->description('SEO metadata')
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Meta Title')
                        ->maxLength(150),

                    Forms\Components\TextInput::make('meta_description')
                        ->label('Meta Description')
                        ->maxLength(255),
                ])
                ->columns(2)
                ->collapsed(),
        ];

        if ($hasFeatureTables) {
            $schema[] = Forms\Components\Section::make('Features')
                ->description('Category feature fields available in ad forms')
                ->schema([
                    Forms\Components\CheckboxList::make('features')
                        ->label('Linked features')
                        ->relationship('features', 'name')
                        ->columns(3)
                        ->searchable(),
                ])
                ->collapsed();
        }

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        $hasFeatureTables = Schema::hasTable('ad_features') && Schema::hasTable('category_feature');

        $columns = [
            Tables\Columns\TextColumn::make('name')
                ->label('Name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('parent.name')
                ->label('Parent')
                ->sortable(),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean(),
        ];

        if ($hasFeatureTables) {
            $columns[] = Tables\Columns\TextColumn::make('features_count')
                ->label('Features')
                ->counts('features');
        }

        return $table
            ->columns($columns)
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdCategories::route('/'),
            'create' => Pages\CreateAdCategory::route('/create'),
            'edit' => Pages\EditAdCategory::route('/{record}/edit'),
        ];
    }
}