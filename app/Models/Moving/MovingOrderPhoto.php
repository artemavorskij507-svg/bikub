<?php

namespace App\Models\Moving;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MovingOrderPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'moving_order_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'latitude',
        'longitude',
        'collection_name',
        'description',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the moving order that owns this photo.
     */
    public function movingOrder(): BelongsTo
    {
        return $this->belongsTo(MovingOrder::class);
    }

    /**
     * Get the full URL to the photo.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Scope to filter by collection name.
     */
    public function scopeInCollection($query, string $collectionName)
    {
        return $query->where('collection_name', $collectionName);
    }

    /**
     * Scope to get photos with GPS coordinates.
     */
    public function scopeWithGps($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }
}
