<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminIpRule extends Model
{
    protected $table = 'admin_ip_rules';

    protected $fillable = ['type', 'ip_range', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
