<?php

namespace App\Observers;

use App\Models\WorkSpecification;

class WorkSpecificationObserver
{
    /**
     * Handle the WorkSpecification "created" event.
     */
    public function created(WorkSpecification $workSpecification): void
    {
        //
    }

    /**
     * Handle the WorkSpecification "updated" event.
     */
    public function updated(WorkSpecification $workSpecification): void
    {
        //
    }

    /**
     * Handle the WorkSpecification "deleted" event.
     */
    public function deleted(WorkSpecification $workSpecification): void
    {
        //
    }

    /**
     * Handle the WorkSpecification "restored" event.
     */
    public function restored(WorkSpecification $workSpecification): void
    {
        //
    }

    /**
     * Handle the WorkSpecification "force deleted" event.
     */
    public function forceDeleted(WorkSpecification $workSpecification): void
    {
        //
    }
}
