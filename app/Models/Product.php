<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'kassal_id',
        'name',
        'slug',
        'sku',
        'description',
        'image_url',
        'is_active',
        'weight_kg',
        'volume_m3',
        'dimensions',
        'unit',
        'canonical_name',
        'source_file',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weight_kg' => 'decimal:2',
        'volume_m3' => 'decimal:3',
        'dimensions' => 'array',
    ];

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'product_store_prices')
            ->withPivot('price')
            ->withTimestamps();
    }

    public function storePrices()
    {
        return $this->hasMany(ProductStorePrice::class);
    }
}
