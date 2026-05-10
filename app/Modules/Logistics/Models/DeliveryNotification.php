<?php

namespace App\Modules\Logistics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNotification extends Model
{
    protected $fillable = ['shipment_id','user_id','channel','status','subject','body','sent_at','metadata'];
    protected $casts = ['sent_at' => 'datetime', 'metadata' => 'array'];

    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
