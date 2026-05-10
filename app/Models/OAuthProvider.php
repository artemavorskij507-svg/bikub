<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthProvider extends Model
{
    protected $table = 'oauth_providers';

    protected $fillable = ['name', 'provider_key', 'config', 'enabled'];

    protected $casts = ['config' => 'array', 'enabled' => 'boolean'];
}
