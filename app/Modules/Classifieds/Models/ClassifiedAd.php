<?php

namespace App\Modules\Classifieds\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ClassifiedAd extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'classified_ads';

    protected $fillable = [
        'user_id',
        'shop_id',
        'category_id',
        'title',
        'description',
        'price_details',
        'price_value',
        'status',
        'address',
        'is_premium',
        'premium_expires_at',
        'published_at',
        'moderation_reason',
        'expires_at',
        'slug',
        'bumped_at',
        'highlight_expires_at',
        'top_expires_at',
        'vip_expires_at',
        'lat',
        'lng',
        'views_count',
    ];

    protected $casts = [
        'price_details' => 'array',
        'is_premium' => 'boolean',
        'premium_expires_at' => 'datetime',
        'published_at' => 'datetime',
        'bumped_at' => 'datetime',
        'highlight_expires_at' => 'datetime',
        'top_expires_at' => 'datetime',
        'vip_expires_at' => 'datetime',
        'expires_at' => 'datetime',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function category()
    {
        return $this->belongsTo(AdCategory::class, 'category_id');
    }

    public function images()
    {
        return $this->hasMany(AdImage::class)->orderBy('sort_order');
    }

    public function getMainImageUrlAttribute()
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('ad_images')) {
            $title = substr((string) $this->title, 0, 20);

            return 'https://placehold.co/600x400/3b82f6/ffffff?text='.urlencode($title);
        }

        $img = $this->images->first();
        if ($img && $img->path) {
            return asset('storage/'.$img->path);
        }
        // Fallback placeholder with better design
        $title = substr($this->title, 0, 20);

        return 'https://placehold.co/600x400/3b82f6/ffffff?text='.urlencode($title);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function hasLocation(): bool
    {
        return ! is_null($this->lat) && ! is_null($this->lng);
    }

    protected function priceFormatted(): Attribute
    {
        return Attribute::get(
            fn () => $this->price_value
                ? number_format($this->price_value / 100, 2, ',', ' ').' NOK'
                : null
        );
    }

    public function scopeNearby(Builder $query, float $lat, float $lng, int $radiusKm): Builder
    {
        // Простая аппроксимация по bounding box (без PostGIS)
        $delta = $radiusKm / 111.0; // ~км в одном градусе широты

        return $query
            ->whereBetween('lat', [$lat - $delta, $lat + $delta])
            ->whereBetween('lng', [$lng - $delta, $lng + $delta]);
    }

    // --- Promotion scopes ---

    public function scopeHighlighted(Builder $query): Builder
    {
        return $query
            ->whereNotNull('highlight_expires_at')
            ->where('highlight_expires_at', '>', now());
    }

    public function scopeTop(Builder $query): Builder
    {
        return $query
            ->whereNotNull('top_expires_at')
            ->where('top_expires_at', '>', now());
    }

    public function scopeVip(Builder $query): Builder
    {
        return $query
            ->whereNotNull('vip_expires_at')
            ->where('vip_expires_at', '>', now());
    }

    public function scopeOrderByPromotion(Builder $query): Builder
    {
        return $query
            ->orderByDesc('vip_expires_at')
            ->orderByDesc('top_expires_at')
            ->orderByDesc('highlight_expires_at')
            ->orderByDesc('bumped_at')
            ->orderByDesc('published_at');
    }

    /**
     * Реєстрація колекцій медіа для оголошення
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('ads')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->singleFile();

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    /**
     * Отримати головне зображення оголошення
     */
    public function getFirstMediaUrl(string $collectionName = 'ads', string $conversionName = ''): ?string
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('media')) {
            return null;
        }

        return $this->getFirstMedia($collectionName)?->getUrl($conversionName);
    }

    /**
     * Отримати всі медіа з колекції
     */
    public function getMediaFromCollection(string $collectionName = 'ads'): \Illuminate\Support\Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('media')) {
            return collect();
        }

        return $this->getMedia($collectionName);
    }
}
