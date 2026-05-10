<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_project_id',
        'repair_stage_id',
        'repair_update_id',
        'type',
        'role',
        'disk',
        'path',
        'thumbnail_path',
        'caption',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(RepairProject::class, 'repair_project_id');
    }

    public function stage()
    {
        return $this->belongsTo(RepairStage::class, 'repair_stage_id');
    }

    public function repairUpdate()
    {
        return $this->belongsTo(RepairUpdate::class, 'repair_update_id');
    }
}
