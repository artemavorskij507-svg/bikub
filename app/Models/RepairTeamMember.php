<?php

namespace App\Models;

use App\Models\Moving\ExecutorProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_project_id',
        'executor_profile_id',
        'role',
        'is_lead',
        'notes',
    ];

    protected $casts = [
        'is_lead' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(RepairProject::class, 'repair_project_id');
    }

    public function executorProfile()
    {
        return $this->belongsTo(ExecutorProfile::class);
    }
}
