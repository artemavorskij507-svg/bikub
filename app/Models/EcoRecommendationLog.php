<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcoRecommendationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'engine_version',
        'features',
        'recommendations',
        'accepted',
    ];

    protected $casts = [
        'features' => 'array',
        'recommendations' => 'array',
        'accepted' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
