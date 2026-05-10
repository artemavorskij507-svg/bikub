<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = ['public_id','name','code','address','city','country_code','latitude','longitude','is_active','metadata'];
    protected $casts = ['is_active' => 'boolean','metadata' => 'array'];

    public function zones(): HasMany { return $this->hasMany(WarehouseZone::class); }
    public function inventoryItems(): HasMany { return $this->hasMany(Inventory::class); }
}
