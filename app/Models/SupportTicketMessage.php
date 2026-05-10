<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketMessage extends Model
{
    protected $table = 'support_ticket_messages';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'sender_type',
        'message',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isFromWorker(): bool
    {
        return $this->sender_type === 'worker';
    }

    public function isFromStaff(): bool
    {
        return in_array($this->sender_type, ['dispatcher', 'admin', 'support'], true);
    }
}
