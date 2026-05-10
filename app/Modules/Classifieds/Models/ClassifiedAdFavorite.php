<?php

namespace App\Modules\Classifieds\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassifiedAdFavorite extends Model
{
    protected $table = 'classified_ad_favorites';

    protected $fillable = [
        'user_id',
        'classified_ad_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo('\App\Models\User');
    }

    public function classifiedAd(): BelongsTo
    {
        return $this->belongsTo(ClassifiedAd::class);
    }
}
