<?php

namespace App\Filament\Resources\ServiceCategoryResource\Pages;

use App\Filament\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateServiceCategory extends CreateRecord
{
    protected static string $resource = ServiceCategoryResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('create_from_template')
                ->label('Create from template')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\Select::make('template')
                        ->label('Category template')
                        ->options([
                            'delivery' => 'Delivery',
                            'moving' => 'Moving',
                            'handyman' => 'Handyman',
                            'cleaning' => 'Cleaning',
                            'food' => 'Food',
                            'eco' => 'Eco disposal',
                            'roadside' => 'Roadside assistance',
                            'social_care' => 'Social care',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $templates = [
                                'delivery' => [
                                    'code' => 'delivery',
                                    'name' => 'Delivery',
                                    'icon' => 'heroicon-o-truck',
                                    'color' => '#3b82f6',
                                    'description' => 'Fast and reliable delivery for goods and products.',
                                ],
                                'moving' => [
                                    'code' => 'moving',
                                    'name' => 'Moving',
                                    'icon' => 'heroicon-o-archive-box',
                                    'color' => '#8b5cf6',
                                    'description' => 'Professional moving and transportation services.',
                                ],
                                'handyman' => [
                                    'code' => 'handyman',
                                    'name' => 'Handyman',
                                    'icon' => 'heroicon-o-briefcase',
                                    'color' => '#f59e0b',
                                    'description' => 'Small repairs and home maintenance services.',
                                ],
                                'cleaning' => [
                                    'code' => 'cleaning',
                                    'name' => 'Cleaning',
                                    'icon' => 'heroicon-o-sparkles',
                                    'color' => '#10b981',
                                    'description' => 'Professional cleaning for homes and offices.',
                                ],
                                'food' => [
                                    'code' => 'food',
                                    'name' => 'Food',
                                    'icon' => 'heroicon-o-cake',
                                    'color' => '#ef4444',
                                    'description' => 'Food delivery from restaurants and cafes.',
                                ],
                                'eco' => [
                                    'code' => 'eco',
                                    'name' => 'Eco disposal',
                                    'icon' => 'heroicon-o-recycle',
                                    'color' => '#059669',
                                    'description' => 'Eco-friendly waste disposal services.',
                                ],
                                'roadside' => [
                                    'code' => 'roadside',
                                    'name' => 'Roadside assistance',
                                    'icon' => 'heroicon-o-truck',
                                    'color' => '#dc2626',
                                    'description' => 'Roadside support and emergency help.',
                                ],
                                'social_care' => [
                                    'code' => 'social_care',
                                    'name' => 'Social care',
                                    'icon' => 'heroicon-o-heart',
                                    'color' => '#ec4899',
                                    'description' => 'Social and care services for citizens.',
                                ],
                            ];

                            if (isset($templates[$state])) {
                                $template = $templates[$state];
                                $set('code', $template['code']);
                                $set('name', $template['name']);
                                $set('slug', Str::slug($template['name']));
                                $set('icon', $template['icon']);
                                $set('color', $template['color']);
                                $set('description', $template['description']);
                                $set('short_description', $template['description']);
                                $set('is_active', true);
                                $set('show_on_homepage', true);

                                $maxSortOrder = ServiceCategory::max('sort_order') ?? 0;
                                $set('sort_order', $maxSortOrder + 1);
                                $set('homepage_order', $maxSortOrder + 1);
                            }
                        }),
                ])
                ->action(function (array $data) {
                    Notification::make()
                        ->title('Template applied')
                        ->body('Form fields were prefilled from template. Verify values and save.')
                        ->info()
                        ->send();
                }),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! isset($data['sort_order']) || $data['sort_order'] === null) {
            $maxSortOrder = ServiceCategory::max('sort_order') ?? 0;
            $data['sort_order'] = $maxSortOrder + 1;
        }

        if (! isset($data['homepage_order']) || $data['homepage_order'] === null) {
            if ($data['show_on_homepage'] ?? false) {
                $maxHomepageOrder = ServiceCategory::max('homepage_order') ?? 0;
                $data['homepage_order'] = $maxHomepageOrder + 1;
            } else {
                $data['homepage_order'] = 0;
            }
        }

        if (isset($data['slug'])) {
            $baseSlug = $data['slug'];
            $counter = 1;
            while (ServiceCategory::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $baseSlug.'-'.$counter;
                $counter++;
            }
        }

        if (isset($data['code'])) {
            $baseCode = $data['code'];
            $counter = 1;
            while (ServiceCategory::where('code', $data['code'])->exists()) {
                $data['code'] = $baseCode.'_'.$counter;
                $counter++;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Category created')
            ->body('Category "'.$this->record->name.'" was created successfully')
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Open')
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record])),
                \Filament\Notifications\Actions\Action::make('create_service')
                    ->label('Create service')
                    ->url(route('filament.resources.service-types.create', ['service_category_id' => $this->record->id]))
                    ->color('success'),
            ]);
    }

    protected function afterCreate(): void
    {
        // Reserved for optional post-create hooks.
    }
}
