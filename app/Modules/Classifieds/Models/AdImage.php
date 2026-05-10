<?php

namespace App\Modules\Classifieds\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AdImage extends Model
{
    protected $fillable = ['classified_ad_id', 'path', 'sort_order'];

    public function classifiedAd(): BelongsTo
    {
        return $this->belongsTo(ClassifiedAd::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
