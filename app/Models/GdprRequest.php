<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'org_id',
        'type',
        'status',
        'description',
        'result_url',
        'metadata',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isExport(): bool
    {
        return $this->type === 'export';
    }

    public function isErasure(): bool
    {
        return $this->type === 'erase';
    }

    public function isRectification(): bool
    {
        return $this->type === 'rectify';
    }

    public function isPortability(): bool
    {
        return $this->type === 'portability';
    }

    public function getEstimatedCompletionTime(): ?Carbon
    {
        return match ($this->type) {
            'export' => $this->created_at->addHours(24),
            'erase' => $this->created_at->addHours(48),
            'rectify' => $this->created_at->addHours(12),
            'portability' => $this->created_at->addHours(24),
            default => null
        };
    }

    public function getStatusDescription(): string
    {
        return match ($this->status) {
            'pending' => 'Request received and queued for processing',
            'processing' => 'Request is being processed',
            'completed' => 'Request has been completed successfully',
            'failed' => 'Request failed to process',
            default => 'Unknown status'
        };
    }

    public function getTypeDescription(): string
    {
        return match ($this->type) {
            'export' => 'Data Export Request',
            'erase' => 'Data Erasure Request',
            'rectify' => 'Data Rectification Request',
            'portability' => 'Data Portability Request',
            default => 'Unknown request type'
        };
    }
}
