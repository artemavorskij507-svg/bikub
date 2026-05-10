<?php

namespace App\Filament\Resources\ServiceCategoryResource\Pages;

use App\Filament\Resources\ServiceCategoryResource;
use App\Filament\Resources\ServiceCategoryResource\Widgets\CategoryServicesWidget;
use App\Filament\Resources\ServiceCategoryResource\Widgets\CategoryStatsWidget;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceCategory extends EditRecord
{
    protected static string $resource = ServiceCategoryResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('create_service')
                ->label('Create service')
                ->color('success')
                ->button()
                ->url(fn () => route('filament.resources.service-types.create', ['service_category_id' => $this->record->id]))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->serviceTypes()->count() === 0),
            Actions\Action::make('view_services')
                ->label('View services')
                ->color('primary')
                ->button()
                ->url(fn () => route('filament.resources.service-types.index', ['tableFilters' => ['service_category_id' => ['value' => $this->record->id]]]))
                ->openUrlInNewTab(),
            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $newCategory = $this->record->replicate();
                    $newCategory->code = $newCategory->code.'_copy_'.time();
                    $newCategory->slug = $newCategory->slug.'-copy-'.time();
                    $newCategory->name = $newCategory->name.' (copy)';
                    $newCategory->is_active = false;
                    $newCategory->save();

                    Notification::make()
                        ->title('Category duplicated')
                        ->body('Created new category: '.$newCategory->name)
                        ->success()
                        ->send();

                    return redirect()->route('filament.resources.service-categories.edit', $newCategory);
                }),
            Actions\Action::make('toggle_active')
                ->label(fn () => $this->record->is_active ? 'Deactivate' : 'Activate')
                ->color(fn () => $this->record->is_active ? 'warning' : 'success')
                ->action(function () {
                    $this->record->update(['is_active' => ! $this->record->is_active]);

                    Notification::make()
                        ->title($this->record->is_active ? 'Category activated' : 'Category deactivated')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Category updated')
            ->body('Changes were saved successfully')
            ->success();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CategoryStatsWidget::class,
            CategoryServicesWidget::class,
        ];
    }

    public function getHeading(): string
    {
        return 'Edit category: '.$this->record->name;
    }

    public function getSubheading(): ?string
    {
        $serviceTypesCount = $this->record->serviceTypes()->count();
        $activeCount = $this->record->serviceTypes()->where('is_active', true)->count();

        return sprintf(
            'ID: %d | Services: %d (%d active) | Status: %s',
            $this->record->id,
            $serviceTypesCount,
            $activeCount,
            $this->record->is_active ? 'Active' : 'Inactive'
        );
    }
}
