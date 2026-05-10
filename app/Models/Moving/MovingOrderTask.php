<?php

namespace App\Models\Moving;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovingOrderTask extends Model
{
    use HasFactory;

    protected $table = 'moving_order_task';

    protected $fillable = [
        'moving_order_id',
        'task_id',
        'task_type',
    ];

    public function movingOrder(): BelongsTo
    {
        return $this->belongsTo(MovingOrder::class, 'moving_order_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}

