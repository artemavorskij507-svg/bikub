<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerApplication extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'city', 'role_requested', 'has_car', 'vehicle_type',
        'license_info', 'languages', 'experience', 'availability', 'work_zones',
        'notes', 'status',
    ];

    protected $casts = [
        'has_car' => 'boolean',
        'languages' => 'array',
        'work_zones' => 'array',
    ];
}

