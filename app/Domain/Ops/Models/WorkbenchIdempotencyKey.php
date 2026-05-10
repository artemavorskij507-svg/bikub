<?php

namespace App\Domain\Ops\Models;

use Illuminate\Database\Eloquent\Model;

class WorkbenchIdempotencyKey extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'action_name',
        'idempotency_key',
        'target_type',
        'target_id',
        'request_hash',
        'state',
        'response_status',
        'response_body_json',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'response_body_json' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}

