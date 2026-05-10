<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'zone_id',
        'logo_url',
        'banner_url',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function zone()
    {
        return $this->belongsTo(GeoZone::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_store_prices')
            ->withPivot('price')
            ->withTimestamps();
    }

    public function storePrices()
    {
        return $this->hasMany(ProductStorePrice::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
