<?php

namespace App\Modules\Classifieds\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdFeature extends Model
{
    protected $table = 'ad_features';

    protected $fillable = [
        'name',
        'code',
        'field_type',
        'options',
        'is_required',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(AdCategory::class, 'category_feature', 'feature_id', 'category_id');
    }
}
