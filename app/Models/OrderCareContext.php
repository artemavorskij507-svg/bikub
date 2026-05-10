<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCareContext extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'client_profile_id',
        'trusted_contact_id',
        'is_vulnerable_client',
        'needs_extra_care',
        'notes_for_performer',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_vulnerable_client' => 'boolean',
        'needs_extra_care' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class);
    }

    public function trustedContact(): BelongsTo
    {
        return $this->belongsTo(TrustedContact::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
