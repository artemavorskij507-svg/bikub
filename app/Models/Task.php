<?php

namespace App\Models;

use App\Services\WebhookNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    private static array $tableColumnCache = [];

    protected $fillable = [
        'order_id', 'parent_task_id', 'sequence_index',
        'type', 'status', 'priority',
        'zone_id', 'slot_id', 'assignee_id',
        'address_text', 'lat', 'lng',
        'window_start', 'window_end', 'expected_duration_min',
        'requirements', 'price_component', 'payout_amount', 'currency',
        'sla_deadline_at', 'proof_required', 'instructions', 'attachments', 'meta',
        'completed_at', // для Roadside jobs
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'window_start' => 'datetime',
        'window_end' => 'datetime',
        'requirements' => 'array',
        'attachments' => 'array',
        'meta' => 'array',
        'proof_required' => 'boolean',
        'sla_deadline_at' => 'datetime',
        'price_component' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function zone()
    {
        return $this->belongsTo(GeoZone::class, 'zone_id');
    }

    public function slot()
    {
        return $this->belongsTo(ScheduleSlot::class, 'slot_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Employee::class, 'assignee_id');
    }

    public function events()
    {
        return $this->hasMany(TaskEvent::class);
    }

    public function dependencies()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id');
    }

    public function blockedBy()
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function blocks()
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
    }

    protected static function booted(): void
    {
        static::updating(function (Task $task) {
            // Validate payment status when assigning task (strict payment gate)
            if ($task->isDirty('assignee_id') && $task->assignee_id && config('feature_flags.strict_payment_gate', false)) {
                $order = $task->order;
                if ($order && ! in_array($order->payment_status, ['paid', 'captured'], true)) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ['payment' => 'Задача не может быть назначена: заказ не оплачен. Статус платежа: '.$order->payment_status]
                    );
                }
            }

            // Validate proof requirement before completing
            if ($task->isDirty('status') && $task->status === 'completed') {
                // Get the original proof_required value before update
                $proofRequired = $task->getOriginal('proof_required') ?? $task->proof_required;

                if ($proofRequired) {
                    // Check if proof attachments exist (after update)
                    $attachments = $task->attachments ?? [];
                    $hasProof = false;

                    foreach ($attachments as $attachment) {
                        if (isset($attachment['type']) && in_array($attachment['type'], ['photo', 'proof', 'signature'], true)) {
                            $hasProof = true;
                            break;
                        }
                    }

                    if (! $hasProof) {
                        throw new \Illuminate\Validation\ValidationException(
                            validator([], []),
                            ['proof' => 'Требуется подтверждение выполнения задачи (proof attachment).']
                        );
                    }
                }
            }
        });

        static::updated(function (Task $task) {
            $notifier = app(WebhookNotifier::class);

            if ($task->isDirty('assignee_id') && $task->assignee_id && ! $task->getOriginal('assignee_id')) {
                $notifier->send('task.assigned', [
                    'task_id' => $task->id,
                    'assignee_id' => $task->assignee_id,
                ]);
            }

            if ($task->wasChanged('status')) {
                $oldStatus = $task->getOriginal('status');
                $newStatus = $task->status;

                // Create TaskEvent for status change
                TaskEvent::create([
                    'task_id' => $task->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'reason' => 'Status changed via admin panel',
                    'payload' => [
                        'changed_at' => now()->toIso8601String(),
                        'changed_by' => auth()->id(),
                    ],
                ]);

                if (in_array($newStatus, ['completed', 'failed'], true)) {
                    $type = $newStatus === 'completed' ? 'task.completed' : 'task.failed';
                    $notifier->send($type, [
                        'task_id' => $task->id,
                        'status' => $newStatus,
                    ]);
                }
            }
        });
    }

    /**
     * Check if task is at SLA risk (deadline approaching within 30 minutes)
     */
    public function isSlaAtRisk(): bool
    {
        if (! $this->sla_deadline_at) {
            return false;
        }

        $deadline = \Carbon\Carbon::parse($this->sla_deadline_at);
        $now = now();

        // At risk if deadline is within 30 minutes and not yet passed
        return $deadline->isFuture() && $deadline->diffInMinutes($now) <= 30;
    }

    /**
     * Check if task SLA is critical (deadline has passed)
     */
    public function isSlaCritical(): bool
    {
        if (! $this->sla_deadline_at) {
            return false;
        }

        $deadline = \Carbon\Carbon::parse($this->sla_deadline_at);

        return $deadline->isPast() && ! in_array($this->status, ['completed', 'canceled'], true);
    }

    /**
     * Check if proof is provided
     */
    public function hasProof(): bool
    {
        if (empty($this->attachments)) {
            return false;
        }

        // Check if any attachment is marked as proof
        foreach ($this->attachments as $attachment) {
            if (isset($attachment['type']) && in_array($attachment['type'], ['photo', 'proof', 'signature'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Backward-compatible checklist completion metric for dispatch/mobile controllers.
     */
    public function getChecklistCompletionPercentage(): int
    {
        $checklist = $this->getChecklistItems();

        if ($checklist === [] || count($checklist) === 0) {
            return 0;
        }

        $completed = collect($checklist)->filter(function ($item) {
            return is_array($item) && (bool) ($item['completed'] ?? false) === true;
        })->count();

        return (int) round(($completed / max(1, count($checklist))) * 100);
    }

    /**
     * Update or append one checklist item.
     */
    public function updateChecklistItem(string $itemId, bool $completed, ?string $note = null): void
    {
        $checklist = $this->getChecklistItems();
        $updated = false;

        foreach ($checklist as &$item) {
            if (! is_array($item)) {
                continue;
            }

            if ((string) ($item['id'] ?? '') === $itemId) {
                $item['completed'] = $completed;
                if ($note !== null) {
                    $item['note'] = $note;
                }
                $item['updated_at'] = now()->toIso8601String();
                $updated = true;
                break;
            }
        }
        unset($item);

        if (! $updated) {
            $checklist[] = [
                'id' => $itemId,
                'completed' => $completed,
                'note' => $note,
                'updated_at' => now()->toIso8601String(),
            ];
        }

        $this->persistChecklist($checklist);
    }

    /**
     * Add proof attachment record.
     */
    public function addProof(string $type, array $proofData): void
    {
        $attachments = is_array($this->attachments) ? $this->attachments : [];
        $attachments[] = array_merge($proofData, [
            'type' => $type,
            'added_at' => now()->toIso8601String(),
        ]);

        if ($this->hasTableColumn('attachments')) {
            $this->attachments = $attachments;
        }

        $meta = is_array($this->meta) ? $this->meta : [];
        $meta['proofs'] = $attachments;
        $this->meta = $meta;

        $this->save();
    }

    /**
     * Mark task as checked in with location snapshot.
     */
    public function checkIn(array $location, ?string $note = null): void
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $meta['checkin'] = [
            'location' => $location,
            'note' => $note,
            'at' => now()->toIso8601String(),
        ];
        $this->meta = $meta;

        if ($this->hasTableColumn('checkin_at')) {
            $this->setAttribute('checkin_at', Carbon::now());
        }

        if (in_array($this->status, ['queued', 'assigned'], true)) {
            $this->status = 'enroute';
        }

        $this->save();
    }

    /**
     * Mark task as checked out / completed with final location snapshot.
     */
    public function checkOut(array $location, ?string $note = null): void
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $meta['checkout'] = [
            'location' => $location,
            'note' => $note,
            'at' => now()->toIso8601String(),
        ];
        $this->meta = $meta;

        $this->status = 'completed';
        if ($this->hasTableColumn('completed_at')) {
            $this->completed_at = Carbon::now();
        }

        $this->save();
    }

    /**
     * Read checklist safely from direct column (if exists) or from meta payload.
     */
    public function getChecklistItems(): array
    {
        if ($this->hasTableColumn('checklist')) {
            $raw = $this->getAttribute('checklist');
            if (is_array($raw)) {
                return $raw;
            }
        }

        return data_get(is_array($this->meta) ? $this->meta : [], 'checklist', []);
    }

    private function persistChecklist(array $checklist): void
    {
        if ($this->hasTableColumn('checklist')) {
            $this->setAttribute('checklist', $checklist);
        }

        $meta = is_array($this->meta) ? $this->meta : [];
        $meta['checklist'] = $checklist;
        $this->meta = $meta;

        $this->save();
    }

    private function hasTableColumn(string $column): bool
    {
        $table = $this->getTable();
        $cacheKey = $table . ':' . $column;

        if (! array_key_exists($cacheKey, self::$tableColumnCache)) {
            self::$tableColumnCache[$cacheKey] = Schema::hasColumn($table, $column);
        }

        return self::$tableColumnCache[$cacheKey];
    }
}
