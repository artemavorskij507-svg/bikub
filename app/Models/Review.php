<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'target_type',
        'target_id',
        'rating',
        'text',
        'tags',
        'status',
        'is_verified',
        'helpful_count',
        'report_count',
        'moderated_by',
        'moderated_at',
        'meta',
    ];

    protected $casts = [
        'tags' => 'array',
        'meta' => 'array',
        'is_verified' => 'boolean',
        'moderated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function helpfulness(): HasMany
    {
        return $this->hasMany(ReviewHelpfulness::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isHidden(): bool
    {
        return $this->status === 'hidden';
    }

    public function isFlagged(): bool
    {
        return $this->status === 'flagged';
    }

    public function incrementHelpfulCount(): void
    {
        $this->increment('helpful_count');
    }

    public function incrementReportCount(): void
    {
        $this->increment('report_count');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForTarget($query, $targetType, $targetId)
    {
        return $query->where('target_type', $targetType)->where('target_id', $targetId);
    }
}
