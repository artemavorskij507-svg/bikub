<?php

namespace App\Modules\Classifieds\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdMessage extends Model
{
    protected $fillable = [
        'ad_id',
        'sender_id',
        'receiver_id',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function ad()
    {
        return $this->belongsTo(ClassifiedAd::class, 'ad_id');
    }
}
