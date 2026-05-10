<?php

namespace App\Models;

use App\Models\Moving\ExecutorProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'client_profile_id',
        'title',
        'description',
        'status',
        'project_manager_id',
        'address_line',
        'postal_code',
        'city',
        'planned_start_at',
        'planned_finish_at',
        'actual_start_at',
        'actual_finish_at',
        'budget_estimate_minor',
        'base_price',
        'estimated_time',
        'region',
        'budget_actual_minor',
        'design_project_url',
        'notes',
        'overall_progress_percent',
    ];

    protected $casts = [
        'planned_start_at' => 'datetime',
        'planned_finish_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_finish_at' => 'datetime',
        'overall_progress_percent' => 'integer',
        'base_price' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function clientProfile()
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(ExecutorProfile::class, 'project_manager_id');
    }

    public function stages()
    {
        return $this->hasMany(RepairStage::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(RepairTeamMember::class);
    }

    public function teamLeads()
    {
        return $this->teamMembers()->where('is_lead', true);
    }

    public function workWarranties()
    {
        return $this->hasMany(WorkWarranty::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function materialsEntries()
    {
        return $this->hasMany(HandymanMaterialsEntry::class);
    }

    public function updates()
    {
        return $this->hasMany(RepairUpdate::class);
    }

    public function media()
    {
        return $this->hasMany(RepairMedia::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            'assessment',
            'estimating',
            'scheduled',
            'in_progress',
            'on_hold',
        ], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
