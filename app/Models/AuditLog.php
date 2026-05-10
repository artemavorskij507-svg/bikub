<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'actor_user_id', 'action', 'model_type', 'model_id', 'before', 'after', 'ip_address', 'user_agent', 'request_id',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'request_id' => 'string',
    ];

    // Read-only records by policy - do not provide mutators in codepaths other than AuditLogger
}
