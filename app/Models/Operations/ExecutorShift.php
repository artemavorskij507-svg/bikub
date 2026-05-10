<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutorShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id','tenant_id','executor_id','day_of_week','start_time','end_time','shift_date','starts_at','ends_at','timezone','is_active','is_available',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Executor::class, 'executor_id');
    }
}
