<?php

namespace App\Domain\Dispatch\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchRuleSet extends Model
{
    protected $table = 'dispatch_rule_sets';

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'service_domain',
        'job_kind',
        'rule_key',
        'rule_value_json',
        'is_active',
    ];

    protected $casts = [
        'rule_value_json' => 'array',
        'is_active' => 'boolean',
    ];
}

