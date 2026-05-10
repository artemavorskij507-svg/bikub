<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id', 'from_status', 'to_status', 'reason', 'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
