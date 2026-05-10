<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcoTeam extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'vehicle_type',
        'vehicle_capacity_m3',
        'vehicle_max_weight_kg',
        'is_active',
    ];

    protected $casts = [
        'vehicle_capacity_m3' => 'decimal:3',
        'vehicle_max_weight_kg' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    // TODO: pivot users relation if needed (eco_team_user)
}
