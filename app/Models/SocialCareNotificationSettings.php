<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialCareNotificationSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notify_care_order_created',
        'notify_care_plan_created',
        'notify_visit_status_changes',
        'notify_visit_reports',
        'notify_emergency',
        'notify_reschedule_requests',
    ];

    protected $casts = [
        'notify_care_order_created' => 'boolean',
        'notify_care_plan_created' => 'boolean',
        'notify_visit_status_changes' => 'boolean',
        'notify_visit_reports' => 'boolean',
        'notify_emergency' => 'boolean',
        'notify_reschedule_requests' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
