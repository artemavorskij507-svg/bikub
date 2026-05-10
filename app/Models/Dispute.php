<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'status',
        'priority',
        'description',
        'timeline',
        'resolution',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'due_date',
        'meta',
    ];

    protected $casts = [
        'timeline' => 'array',
        'resolution' => 'array',
        'meta' => 'array',
        'resolved_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(DisputeEvidence::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date < now() && ! $this->isResolved();
    }

    public function addTimelineEvent(string $event, array $data = []): void
    {
        $timeline = $this->timeline ?? [];
        $timeline[] = [
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
        ];
        $this->timeline = $timeline;
        $this->save();
    }
}
