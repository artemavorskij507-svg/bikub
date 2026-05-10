<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'base_minutes',
        'night_coef',
        'snow_coef',
        'overload_coef',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'night_coef' => 'decimal:2',
        'snow_coef' => 'decimal:2',
        'overload_coef' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
