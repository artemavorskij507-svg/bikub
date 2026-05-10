<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'repair_project_id',
        'opened_by_user_id',
        'assigned_to_user_id',
        'type',
        'status',
        'severity',
        'title',
        'description',
        'resolution_notes',
        'resolution_type',
        'opened_at',
        'resolved_at',
        'sla_response_due_at',
        'sla_resolution_due_at',
        'responded_at',
        'sla_response_breached',
        'sla_resolution_breached',
        'meta',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
        'sla_response_due_at' => 'datetime',
        'sla_resolution_due_at' => 'datetime',
        'responded_at' => 'datetime',
        'sla_response_breached' => 'boolean',
        'sla_resolution_breached' => 'boolean',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function repairProject()
    {
        return $this->belongsTo(RepairProject::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages()
    {
        return $this->hasMany(ClaimMessage::class)->orderBy('created_at');
    }
}
