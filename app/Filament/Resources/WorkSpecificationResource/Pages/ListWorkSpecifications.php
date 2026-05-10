<?php

namespace App\Filament\Resources\WorkSpecificationResource\Pages;

use App\Filament\Resources\WorkSpecificationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkSpecifications extends ListRecords
{
    protected static string $resource = WorkSpecificationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Убираем фильтр pending_ack из активных по умолчанию
        if (isset($this->tableFilters['pending_ack'])) {
            unset($this->tableFilters['pending_ack']);
        }
    }
}
