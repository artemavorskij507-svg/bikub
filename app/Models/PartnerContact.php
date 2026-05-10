<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerContact extends Model
{
    use HasFactory, Uuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
        'notify' => 'array',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
