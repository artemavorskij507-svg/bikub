<?php

namespace App\Filament\Resources\CarePlanResource\Pages;

use App\Events\SocialCare\CarePlanCreated;
use App\Filament\Resources\CarePlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCarePlan extends CreateRecord
{
    protected static string $resource = CarePlanResource::class;

    protected function afterCreate(): void
    {
        // Dispatch event for notifications
        event(new CarePlanCreated($this->record, auth()->user()));
    }
}
