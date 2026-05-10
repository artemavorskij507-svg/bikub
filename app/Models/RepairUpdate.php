<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_project_id',
        'repair_stage_id',
        'author_user_id',
        'type',
        'title',
        'body',
        'status_snapshot',
        'progress_percent',
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

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function media()
    {
        return $this->hasMany(RepairMedia::class, 'repair_update_id');
    }
}
