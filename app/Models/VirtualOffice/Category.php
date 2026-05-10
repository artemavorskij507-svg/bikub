<?php

namespace App\Models\VirtualOffice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'sector_x_min',
        'sector_x_max',
        'sector_y_min',
        'sector_y_max',
    ];

    protected $casts = [
        'sector_x_min' => 'integer',
        'sector_x_max' => 'integer',
        'sector_y_min' => 'integer',
        'sector_y_max' => 'integer',
    ];

    /**
     * Связь с агентами категории
     */
    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'category_id');
    }

    /**
     * Получить активных агентов категории
     */
    public function activeAgents(): HasMany
    {
        return $this->hasMany(Agent::class, 'category_id')->where('is_active', true);
    }

    /**
     * Проверить, находится ли точка в секторе категории
     */
    public function containsPoint(int $x, int $y): bool
    {
        return $x >= $this->sector_x_min
            && $x <= $this->sector_x_max
            && $y >= $this->sector_y_min
            && $y <= $this->sector_y_max;
    }

    /**
     * Получить случайную позицию в секторе
     */
    public function getRandomPosition(): array
    {
        return [
            'x' => rand($this->sector_x_min, $this->sector_x_max),
            'y' => rand($this->sector_y_min, $this->sector_y_max),
        ];
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
}
