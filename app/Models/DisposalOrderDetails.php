<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisposalOrderDetails extends Model
{
    use HasFactory;

    protected $table = 'disposal_order_details';

    protected $fillable = [
        'order_id',
        'items',
        'floor',
        'has_elevator',
        'parking_distance_m',
        'requires_dismantling',
        'express_requested',
        'estimated_volume_m3',
        'estimated_weight_kg',
        'estimated_price_nok',
        'eco_partner_hint_id',
        'eco_team_id',
        'eco_partner_id',
        'eco_status',
    ];

    protected $casts = [
        'items' => 'array',
        'has_elevator' => 'boolean',
        'requires_dismantling' => 'boolean',
        'express_requested' => 'boolean',
        'estimated_volume_m3' => 'decimal:3',
        'estimated_weight_kg' => 'decimal:3',
        'estimated_price_nok' => 'decimal:2',
        'eco_status' => 'string',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function ecoPartnerHint()
    {
        return $this->belongsTo(DisposalPartner::class, 'eco_partner_hint_id');
    }

    public function ecoTeam()
    {
        return $this->belongsTo(EcoTeam::class, 'eco_team_id');
    }

    public function ecoPartner()
    {
        return $this->belongsTo(DisposalPartner::class, 'eco_partner_id');
    }

    // TODO: helper methods to aggregate items summary and pricing
}
