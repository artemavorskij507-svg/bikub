<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'preset_id',
        'title',
        'description',
        'severity_level',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the preset this item belongs to.
     */
    public function preset(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionPreset::class, 'preset_id');
    }
}
