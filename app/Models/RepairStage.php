<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_project_id',
        'name',
        'description',
        'sequence',
        'status',
        'planned_start_at',
        'planned_finish_at',
        'actual_start_at',
        'actual_finish_at',
        'progress_percent',
    ];

    protected $casts = [
        'planned_start_at' => 'datetime',
        'planned_finish_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_finish_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(RepairProject::class, 'repair_project_id');
    }

    public function updates()
    {
        return $this->hasMany(RepairUpdate::class, 'repair_stage_id');
    }

    public function media()
    {
        return $this->hasMany(RepairMedia::class, 'repair_stage_id');
    }

    // TODO: link stages to sub-orders (eco / handyman / deliveries) for better tracking.
}
