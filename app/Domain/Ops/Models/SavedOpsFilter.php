<?php

namespace App\Domain\Ops\Models;

use Illuminate\Database\Eloquent\Model;

class SavedOpsFilter extends Model
{
    protected $table = 'ops_saved_filters';

    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'filters_json',
        'is_shared',
    ];

    protected $casts = [
        'filters_json' => 'array',
        'is_shared' => 'boolean',
    ];
}

