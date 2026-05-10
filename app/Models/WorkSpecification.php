<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSpecification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'public_id',
        'title',
        'description',
        'status',
        'priority',
        'order_id',
        'ticket_id',
        'responsible_id',
        'creator_id',
        'worker_acknowledged_at',
        'metadata',
    ];

    protected $casts = [
        'worker_acknowledged_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Создатель ТЗ.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Связанный заказ.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Связанный тикет поддержки.
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Исполнитель, ответственный за выполнение ТЗ.
     */
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}
