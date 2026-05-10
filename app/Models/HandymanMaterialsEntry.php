<?php

namespace App\Models;

use App\Models\Moving\ExecutorProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanMaterialsEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'repair_project_id',
        'executor_profile_id',
        'description',
        'quantity',
        'unit',
        'unit_price_minor',
        'total_price_minor',
        'purchased_at',
        'receipt_url',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'float',
        'purchased_at' => 'datetime',
        'meta' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function repairProject()
    {
        return $this->belongsTo(RepairProject::class);
    }

    public function executorProfile()
    {
        return $this->belongsTo(ExecutorProfile::class);
    }
}
