<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisposalItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'volume_m3',
        'weight_kg',
        'requires_disassembly',
        'difficulty_coefficient',
        'disposal_path',
        'eco_score',
        'base_price_nok',
        'is_active',
    ];

    protected $casts = [
        'volume_m3' => 'decimal:3',
        'weight_kg' => 'decimal:3',
        'requires_disassembly' => 'boolean',
        'difficulty_coefficient' => 'decimal:2',
        'eco_score' => 'integer',
        'base_price_nok' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
