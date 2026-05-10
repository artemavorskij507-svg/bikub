<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleInspectionPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'price',
        'description',
        'checklist',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'checklist' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get inspection requests using this preset.
     */
    public function inspectionRequests(): HasMany
    {
        return $this->hasMany(VehicleInspectionRequest::class, 'preset_id');
    }

    /**
     * Get checklist items for this preset.
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(InspectionChecklistItem::class, 'preset_id');
    }
}
