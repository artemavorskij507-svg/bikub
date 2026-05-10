<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'street_address',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'formatted_address',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
