<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerContract extends Model
{
    use HasFactory, Uuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'insurance_valid_to' => 'date',
        'terms' => 'array',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
