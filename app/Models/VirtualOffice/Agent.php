<?php

namespace App\Models\VirtualOffice;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;

    protected $table = 'agents';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category_id',
        'zone_id',
        'x_position',
        'y_position',
        'avatar',
        'emoji',
        'color',
        'is_active',
        'source_file',
        'config',
    ];

    protected $casts = [
        'x_position' => 'integer',
        'y_position' => 'integer',
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    /**
     * Связь с категорией
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Связь с зоной
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(OfficeZone::class, 'zone_id');
    }

    public function currentZone(): BelongsTo
    {
        return $this->zone();
    }

    /**
     * Связь с задачами
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'agent_id');
    }

    /**
     * Связь с сообщениями
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'agent_id');
    }

    /**
     * Получить активные задачи агента
     */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'agent_id')
            ->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * Получить завершенные задачи агента
     */
    public function completedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'agent_id')
            ->where('status', 'completed');
    }

    /**
     * Переместить агента в новую позицию
     */
    public function moveTo(int $x, int $y): bool
    {
        return $this->update([
            'x_position' => $x,
            'y_position' => $y,
        ]);
    }

    /**
     * Переместить агента в зону
     */
    public function moveToZone(OfficeZone $zone): bool
    {
        $center = $zone->getCenter();
        return $this->update([
            'zone_id' => $zone->id,
            'x_position' => $center['x'],
            'y_position' => $center['y'],
        ]);
    }

    /**
     * Проверить, находится ли агент в зоне
     */
    public function isInZone(OfficeZone $zone): bool
    {
        return $zone->containsPoint($this->x_position, $this->y_position);
    }

    /**
     * Получить позицию агента
     */
    public function getPosition(): array
    {
        return [
            'x' => $this->x_position,
            'y' => $this->y_position,
        ];
    }

    /**
     * Деактивировать агента
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Активировать агента
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Scope для активных агентов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для поиска по slug
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope для поиска по имени
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * Scope для фильтрации по категории
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope для фильтрации по зоне
     */
    public function scopeByZone($query, int $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    /**
     * Scope для фильтрации по позиции
     */
    public function scopeByPosition($query, int $x, int $y, int $radius = 50)
    {
        return $query->whereBetween('x_position', [$x - $radius, $x + $radius])
            ->whereBetween('y_position', [$y - $radius, $y + $radius]);
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active ? 'active' : 'offline',
            set: fn (?string $value) => [
                'is_active' => in_array($value, ['active', 'busy', 'idle'], true),
            ],
        );
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->category?->name ?? $this->slug,
        );
    }

    protected function positionX(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value, array $attributes) => $attributes['x_position'] ?? null,
            set: fn (?int $value) => ['x_position' => $value],
        );
    }

    protected function positionY(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value, array $attributes) => $attributes['y_position'] ?? null,
            set: fn (?int $value) => ['y_position' => $value],
        );
    }
}
