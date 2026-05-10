<?php

namespace App\Listeners;

use App\Events\ClaimCreated;
use App\Events\ClaimMessageCreated;
use App\Events\ClaimSlaBreached;
use App\Services\N8nWebhookService;

class SendClaimWebhookToN8n
{
    public function __construct(
        protected N8nWebhookService $webhookService
    ) {}

    public function handleClaimCreated(ClaimCreated $event): void
    {
        $claim = $event->claim->load(['user', 'order', 'repairProject']);

        $this->webhookService->sendClaimCreated([
            'claim_id' => $claim->id,
            'user_id' => $claim->user_id,
            'order_id' => $claim->order_id,
            'repair_project_id' => $claim->repair_project_id,
            'type' => $claim->type,
            'status' => $claim->status,
            'severity' => $claim->severity,
            'title' => $claim->title,
            'sla_response_due_at' => $claim->sla_response_due_at?->toIso8601String(),
            'sla_resolution_due_at' => $claim->sla_resolution_due_at?->toIso8601String(),
            'created_at' => $claim->created_at->toIso8601String(),
        ]);
    }

    public function handleClaimMessageCreated(ClaimMessageCreated $event): void
    {
        $message = $event->message->load(['claim', 'sender']);

        $this->webhookService->sendClaimMessageCreated([
            'message_id' => $message->id,
            'claim_id' => $message->claim_id,
            'sender_id' => $message->sender_id,
            'sender_role' => $message->sender_role,
            'body' => $message->body,
            'created_at' => $message->created_at->toIso8601String(),
        ]);
    }

    public function handleClaimSlaBreached(ClaimSlaBreached $event): void
    {
        $claim = $event->claim->load(['user', 'order']);

        $this->webhookService->sendClaimSlaBreached([
            'claim_id' => $claim->id,
            'breach_type' => $event->breachType,
            'user_id' => $claim->user_id,
            'order_id' => $claim->order_id,
            'sla_response_due_at' => $claim->sla_response_due_at?->toIso8601String(),
            'sla_resolution_due_at' => $claim->sla_resolution_due_at?->toIso8601String(),
            'responded_at' => $claim->responded_at?->toIso8601String(),
            'resolved_at' => $claim->resolved_at?->toIso8601String(),
            'breached_at' => now()->toIso8601String(),
        ]);
    }
}
