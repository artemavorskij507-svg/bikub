<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    protected $table = 'support_tickets';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'number',
        'user_id',
        'role_context',
        'subject',
        'message', // В таблице колонка называется message
        'status',
        'priority',
        'source',
        'channel',
        'resolved_at',
        'resolved_by',
        'metadata',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->number)) {
                // Генерируем номер тикета: TKT-2025-0001
                $year = date('Y');
                // Ищем последний номер за текущий год
                $lastTicket = static::where('number', 'like', "TKT-{$year}-%")
                    ->orderByDesc('number')
                    ->first();

                if ($lastTicket && preg_match('/TKT-\d{4}-(\d+)/', $lastTicket->number, $matches)) {
                    $nextNum = (int) $matches[1] + 1;
                } else {
                    $nextNum = 1;
                }

                $model->number = 'TKT-'.$year.'-'.str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
    }
}
