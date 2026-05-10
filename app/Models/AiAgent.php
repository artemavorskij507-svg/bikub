<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department',
        'status',
        'is_core',
        'permissions_level',
        'description',
    ];

    protected $casts = [
        'is_core' => 'boolean',
    ];
}
