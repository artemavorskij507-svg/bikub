<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareOrderDetails extends Model
{
    use HasFactory;

    protected $table = 'care_order_details';

    protected $fillable = [
        'order_id',
        'client_profile_id',
        'trusted_contact_id',
        'care_service_id',
        'care_plan_id',
        'care_status',
        'scheduled_start_at',
        'scheduled_end_at',
        'assigned_helper_id',
        'requested_helper_level',
        'price_nok',
        'notes_for_helper',
        'internal_notes',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'price_nok' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }

    public function trustedContact(): BelongsTo
    {
        return $this->belongsTo(TrustedContact::class, 'trusted_contact_id');
    }

    public function careService(): BelongsTo
    {
        return $this->belongsTo(CareService::class, 'care_service_id');
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class, 'care_plan_id');
    }

    public function assignedHelper(): BelongsTo
    {
        return $this->belongsTo(SocialHelperProfile::class, 'assigned_helper_id');
    }

    public function visitReports(): HasMany
    {
        return $this->hasMany(VisitReport::class, 'care_order_details_id');
    }
}
