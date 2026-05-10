<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteOptimizationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'status',
        'input_data',
        'result_data',
        'optimization_time_ms',
        'error',
    ];

    protected $casts = [
        'input_data' => 'array',
        'result_data' => 'array',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
