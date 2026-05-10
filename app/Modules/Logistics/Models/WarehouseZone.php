<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseZone extends Model
{
    protected $fillable = ['warehouse_id','name','code','zone_type','capacity','metadata'];
    protected $casts = ['metadata' => 'array'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
}
