<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskFailed;
use App\Services\WebhookNotifier;

class SendTaskWebhook
{
    public function __construct(
        protected WebhookNotifier $notifier
    ) {}

    public function handleTaskAssigned(TaskAssigned $event): void
    {
        $this->notifier->notify('task.assigned', [
            'task_id' => $event->task->id,
            'assignee_id' => $event->assignee->id,
            'order_id' => $event->task->order_id,
        ]);
    }

    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $this->notifier->notify('task.completed', [
            'task_id' => $event->task->id,
            'order_id' => $event->task->order_id,
            'status' => $event->task->status,
        ]);
    }

    public function handleTaskFailed(TaskFailed $event): void
    {
        $this->notifier->notify('task.failed', [
            'task_id' => $event->task->id,
            'order_id' => $event->task->order_id,
            'reason' => $event->reason,
        ]);
    }
}
