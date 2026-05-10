<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleInspectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'preset_id',
        'assigned_helper_id',
        'order_id',
        'seller_name',
        'seller_phone',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'vin_code',
        'address',
        'requested_time',
        'status',
        'report_json',
        'metadata',
    ];

    protected $casts = [
        'requested_time' => 'datetime',
        'report_json' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the customer who requested the inspection.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the inspection preset.
     */
    public function preset(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionPreset::class, 'preset_id');
    }

    /**
     * Get the helper assigned to this inspection.
     */
    public function helper(): BelongsTo
    {
        return $this->belongsTo(RoadHelperProfile::class, 'assigned_helper_id');
    }

    /**
     * Get the order associated with this inspection.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get checklist items for this preset.
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(InspectionChecklistItem::class, 'preset_id', 'preset_id');
    }
}
