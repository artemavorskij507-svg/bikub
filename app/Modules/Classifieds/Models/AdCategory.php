<?php

namespace App\Modules\Classifieds\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCategory extends Model
{
    protected $table = 'ad_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(AdFeature::class, 'category_feature', 'category_id', 'feature_id');
    }

    public function ads(): HasMany
    {
        return $this->hasMany(ClassifiedAd::class, 'category_id');
    }
}
