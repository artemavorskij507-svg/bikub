<?php

namespace App\Models\VirtualOffice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable = [
        'title',
        'description',
        'agent_id',
        'status',
        'priority',
        'deadline',
        'completed_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Статусы задач
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Приоритеты задач
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * Связь с агентом
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Начать выполнение задачи
     */
    public function start(): bool
    {
        return $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    /**
     * Завершить задачу
     */
    public function complete(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Отменить задачу
     */
    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Проверить, просрочена ли задача
     */
    public function isOverdue(): bool
    {
        if (!$this->deadline) {
            return false;
        }

        return $this->deadline->isPast() && $this->status !== self::STATUS_COMPLETED;
    }

    /**
     * Получить оставшееся время до дедлайна
     */
    public function getTimeRemaining(): ?string
    {
        if (!$this->deadline) {
            return null;
        }

        return $this->deadline->diffForHumans();
    }

    /**
     * Scope для активных задач
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Scope для завершенных задач
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope для просроченных задач
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereNot('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope для фильтрации по статусу
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope для фильтрации по приоритету
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope для фильтрации по агенту
     */
    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope для сортировки по приоритету
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("
            CASE priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END
        ");
    }
}
