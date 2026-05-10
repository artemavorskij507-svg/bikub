<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutorLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'executor_id',
        'organization_id',
        'tenant_id',
        'assignment_id',
        'service_job_id',
        'lat',
        'lng',
        'latitude',
        'longitude',
        'speed_kmh',
        'speed',
        'heading',
        'accuracy',
        'recorded_at',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'speed_kmh' => 'decimal:2',
        'speed' => 'decimal:2',
        'accuracy' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Executor::class);
    }
}
