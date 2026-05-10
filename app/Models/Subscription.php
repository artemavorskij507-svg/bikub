<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->current_period_end > now();
    }

    public function isExpired(): bool
    {
        return $this->current_period_end < now();
    }

    public function daysUntilExpiry(): int
    {
        return max(0, now()->diffInDays($this->current_period_end, false));
    }

    public function renew(): void
    {
        $this->current_period_start = $this->current_period_end;
        $this->current_period_end = $this->calculateNextPeriodEnd();
        $this->save();
    }

    private function calculateNextPeriodEnd(): Carbon
    {
        $start = $this->current_period_start;

        return match ($this->plan->period) {
            'monthly' => $start->addMonth(),
            'quarterly' => $start->addMonths(3),
            'yearly' => $start->addYear(),
            default => $start->addMonth()
        };
    }
}
