<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'status',
        'method',
        'note',
        'admin_note',
        'processed_at',
        'processed_by',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this payout.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who processed this payout.
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Check if payout is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payout is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if payout is paid/completed.
     */
    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'completed']);
    }

    /**
     * Check if payout is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
