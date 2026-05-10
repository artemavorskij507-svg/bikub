<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisposalPartner extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'address',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'opening_hours',
        'accepted_categories',
        'requirements',
        'licenses',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'opening_hours' => 'array',
        'accepted_categories' => 'array',
        'licenses' => 'array',
        'is_active' => 'boolean',
    ];

    // TODO: hasMany relations to DisposalOrderDetails or EcoCertificate if applicable
}
