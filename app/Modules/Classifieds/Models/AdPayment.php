<?php

namespace App\Modules\Classifieds\Models;

use Illuminate\Database\Eloquent\Model;

class AdPayment extends Model
{
    protected $table = 'ad_payments';

    protected $fillable = [
        'ad_id',
        'service_type',
        'amount',
        'currency',
        'order_id',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function ad()
    {
        return $this->belongsTo(ClassifiedAd::class, 'ad_id');
    }
}
