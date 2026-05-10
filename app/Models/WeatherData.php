<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_code',
        'date',
        'time',
        'temperature',
        'humidity',
        'wind_speed',
        'precipitation',
        'condition',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'date' => 'date',
        'time' => 'datetime',
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'wind_speed' => 'decimal:2',
        'precipitation' => 'decimal:2',
    ];
}
