<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ServiceCategory extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name', 'short_description', 'description'];

    protected $fillable = [
        'code',
        'slug',
        'name',
        'short_description',
        'description',
        'icon',
        'icon_svg',
        'color',
        'is_active',
        'show_on_homepage',
        'homepage_order',
        'order_column',
        'sort_order',
        'metadata',
        'canonical_code',
        'default_pricing_group',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_homepage' => 'boolean',
        'homepage_order' => 'integer',
        'order_column' => 'integer',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function serviceTypes()
    {
        return $this->hasMany(ServiceType::class, 'service_category_id');
    }

    public function scopeHomepage($query)
    {
        return $query->where('is_active', true)
            ->where('show_on_homepage', true)
            ->orderBy('homepage_order')
            ->orderBy('name');
    }

    public function getDisplayIconAttribute(): ?string
    {
        if ($this->icon_svg) {
            return $this->icon_svg;
        }

        if ($this->icon && str_contains($this->icon, '<svg')) {
            return $this->icon;
        }

        return null;
    }
}
