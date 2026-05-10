<?php

namespace App\Modules\Classifieds\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassifiedAdBump extends Model
{
    protected $table = 'classified_ad_bumps';

    protected $fillable = [
        'classified_ad_id',
        'user_id',
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
