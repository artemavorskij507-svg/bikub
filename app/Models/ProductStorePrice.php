<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStorePrice extends Model
{
    use HasFactory;

    protected $table = 'product_store_prices';

    protected $fillable = [
        'product_id',
        'store_id',
        'price',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    protected function priceNok(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value, array $attributes) => isset($attributes['price'])
                ? $attributes['price'] / 100
                : null,
            set: fn (?string $value) => [
                'price' => $value !== null
                    ? (int) round((float) $value * 100)
                    : null,
            ],
        );
    }
}
