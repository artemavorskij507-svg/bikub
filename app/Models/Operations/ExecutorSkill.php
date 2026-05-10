<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutorSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'executor_id',
        'skill_code',
        'skill_level',
    ];

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Executor::class);
    }
}

