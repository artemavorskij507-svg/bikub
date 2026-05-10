<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parcel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shipment_id','public_id','barcode','qr_code','weight_kg','length_cm','width_cm','height_cm','status','metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
}
