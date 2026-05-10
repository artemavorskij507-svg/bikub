<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_profile_id',
        'user_id',
        'full_name',
        'relationship',
        'phone',
        'email',
        'can_manage_orders',
        'can_view_reports',
        'is_primary',
    ];

    protected $casts = [
        'can_manage_orders' => 'boolean',
        'can_view_reports' => 'boolean',
        'is_primary' => 'boolean',
    ];

    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
