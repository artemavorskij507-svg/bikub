<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrandOrderDetails extends Model
{
    use HasFactory;

    protected $table = 'errand_order_details';

    protected $fillable = [
        'order_id',
        'category',
        'description',
        'from_address',
        'to_address',
        'from_lat',
        'from_lng',
        'to_lat',
        'to_lng',
        'waypoints',
        'contacts',
        'desired_start_at',
        'desired_finish_at',
        'is_urgent',
        'requires_signature',
        'requires_trusted_helper',
        'involves_documents',
        'complexity_level',
        'expected_duration_minutes',
        'material_advance_amount',
        'base_fee',
        'distance_fee',
        'time_fee',
        'complexity_fee',
        'trusted_helper_fee',
        'urgency_fee',
        'total_estimated_price',
        'dispatcher_id',
        'executor_profile_id',
        'meta',
    ];

    protected $casts = [
        'waypoints' => 'array',
        'contacts' => 'array',
        'meta' => 'array',
        'desired_start_at' => 'datetime',
        'desired_finish_at' => 'datetime',
        'is_urgent' => 'boolean',
        'requires_signature' => 'boolean',
        'requires_trusted_helper' => 'boolean',
        'involves_documents' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function executorProfile(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Moving\ExecutorProfile::class, 'executor_profile_id');
    }
}
