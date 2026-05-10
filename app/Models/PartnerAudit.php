<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerAudit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
