<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $fillable = ['warehouse_id','warehouse_zone_id','sku','name','quantity','reserved_quantity','metadata'];
    protected $casts = ['metadata' => 'array'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function zone(): BelongsTo { return $this->belongsTo(WarehouseZone::class, 'warehouse_zone_id'); }
}
