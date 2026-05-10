<?php

namespace App\Listeners;

use App\Events\RepairProjectCreated;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class EmitRepairWebhookForN8n
{
    public function handle(RepairProjectCreated $event): void
    {
        if (! Config::get('services.n8n.enabled')) {
            return;
        }

        $project = $event->project->load('order');

        Http::asJson()
            ->timeout(5)
            ->post(Config::get('services.n8n.webhook_url'), [
                'event' => 'repair.project_created',
                'project_id' => $project->id,
                'order_id' => $project->order?->id,
                'user_id' => $project->order?->user_id,
                'status' => $project->status,
                'created_at' => $project->created_at?->toIso8601String(),
            ]);
    }
}
