<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    protected $fillable = [
        'provider', 'event_type', 'external_id', 'status', 'http_status', 'payload', 'error_message', 'request_id', 'received_at', 'processed_at', 'attempt', 'order_id', 'payment_id', 'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
