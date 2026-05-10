<?php

namespace App\Models\VirtualOffice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $fillable = [
        'agent_id',
        'user_id',
        'content',
        'role',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Роли сообщений
     */
    const ROLE_USER = 'user';
    const ROLE_AGENT = 'agent';

    /**
     * Связь с агентом
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Проверить, является ли сообщение от пользователя
     */
    public function isFromUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Проверить, является ли сообщение от агента
     */
    public function isFromAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }

    /**
     * Scope для сообщений от пользователя
     */
    public function scopeFromUser($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Scope для сообщений от агента
     */
    public function scopeFromAgent($query)
    {
        return $query->where('role', self::ROLE_AGENT);
    }

    /**
     * Scope для сообщений агента
     */
    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope для сообщений пользователя
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope для последних сообщений
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope для поиска по содержимому
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('content', 'like', "%{$term}%");
    }
}
