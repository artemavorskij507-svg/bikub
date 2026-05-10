<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialCareEmergencyEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'helper_profile_id',
        'client_profile_id',
        'triggered_by_user_id',
        'source',
        'level',
        'message',
        'handled_by_user_id',
        'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function helperProfile(): BelongsTo
    {
        return $this->belongsTo(SocialHelperProfile::class, 'helper_profile_id');
    }

    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    public function isHandled(): bool
    {
        return $this->handled_at !== null;
    }
}
