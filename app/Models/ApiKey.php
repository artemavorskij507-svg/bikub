<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = ['owner_type', 'owner_id', 'name', 'key_hash', 'scopes', 'last_used_at', 'expires_at', 'revoked_at'];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // Key material is never stored plaintext. Use service to create and show only once.
}
