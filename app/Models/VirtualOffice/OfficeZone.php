<?php

namespace App\Models\VirtualOffice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeZone extends Model
{
    use HasFactory;

    protected $table = 'office_zones';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'x_min',
        'x_max',
        'y_min',
        'y_max',
        'capacity',
        'amenities',
    ];

    protected $casts = [
        'x_min' => 'integer',
        'x_max' => 'integer',
        'y_min' => 'integer',
        'y_max' => 'integer',
        'capacity' => 'integer',
        'amenities' => 'array',
    ];

    /**
     * Связь с агентами в зоне
     */
    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'zone_id');
    }

    /**
     * Получить активных агентов в зоне
     */
    public function activeAgents(): HasMany
    {
        return $this->hasMany(Agent::class, 'zone_id')->where('is_active', true);
    }

    /**
     * Проверить, находится ли точка в зоне
     */
    public function containsPoint(int $x, int $y): bool
    {
        return $x >= $this->x_min
            && $x <= $this->x_max
            && $y >= $this->y_min
            && $y <= $this->y_max;
    }

    /**
     * Получить центр зоны
     */
    public function getCenter(): array
    {
        return [
            'x' => (int) (($this->x_min + $this->x_max) / 2),
            'y' => (int) (($this->y_min + $this->y_max) / 2),
        ];
    }

    /**
     * Получить размеры зоны
     */
    public function getSize(): array
    {
        return [
            'width' => $this->x_max - $this->x_min,
            'height' => $this->y_max - $this->y_min,
        ];
    }

    /**
     * Проверить, заполнена ли зона
     */
    public function isFull(): bool
    {
        return $this->activeAgents()->count() >= $this->capacity;
    }

    /**
     * Получить количество свободных мест
     */
    public function getAvailableSpots(): int
    {
        return max(0, $this->capacity - $this->activeAgents()->count());
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
        return $query->where('name', 'like', "%{$term}%");
    }
}
