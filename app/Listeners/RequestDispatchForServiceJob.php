<?php

namespace App\Listeners;

use App\Events\ServiceJobCreated;
use App\Jobs\CalculateDispatchCandidatesJob;

class RequestDispatchForServiceJob
{
    public function handle(ServiceJobCreated $event): void
    {
        CalculateDispatchCandidatesJob::dispatch($event->job->id)
            ->onQueue('dispatch-default');
    }
}

