<?php

namespace App\Modules\AgencyAgents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeZone extends Model
{
    use HasFactory;

    protected $table = 'agency_office_zones';

    protected $fillable = [
        'name',
        'display_name',
        'icon',
        'color',
        'bounds',
        'capacity',
        'current_occupancy',
        'amenities',
    ];

    protected $casts = [
        'bounds' => 'array',
        'amenities' => 'array',
        'capacity' => 'integer',
        'current_occupancy' => 'integer',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'current_zone', 'name');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AgentActivity::class, 'zone', 'name');
    }

    public function isFull(): bool
    {
        return $this->current_occupancy >= $this->capacity;
    }

    public function getAvailableSpots(): int
    {
        return max(0, $this->capacity - $this->current_occupancy);
    }

    public function getOccupancyPercentage(): float
    {
        if ($this->capacity === 0) {
            return 0;
        }
        return ($this->current_occupancy / $this->capacity) * 100;
    }

    public function incrementOccupancy(): void
    {
        $this->increment('current_occupancy');
    }

    public function decrementOccupancy(): void
    {
        if ($this->current_occupancy > 0) {
            $this->decrement('current_occupancy');
        }
    }

    public function updateOccupancy(): void
    {
        $count = Agent::where('current_zone', $this->name)->count();
        $this->update(['current_occupancy' => $count]);
    }

    public function getRandomPosition(): array
    {
        $bounds = $this->bounds;
        
        return [
            'x' => rand($bounds['x_min'] + 20, $bounds['x_max'] - 20),
            'y' => rand($bounds['y_min'] + 20, $bounds['y_max'] - 20),
        ];
    }

    public function isPointInZone(float $x, float $y): bool
    {
        $bounds = $this->bounds;
        
        return $x >= $bounds['x_min'] && $x <= $bounds['x_max'] &&
               $y >= $bounds['y_min'] && $y <= $bounds['y_max'];
    }

    public static function getZoneForPosition(float $x, float $y): ?self
    {
        return self::all()->first(function ($zone) use ($x, $y) {
            return $zone->isPointInZone($x, $y);
        });
    }

    public static function initializeDefaultZones(): void
    {
        $defaultZones = [
            [
                'name' => 'workspace',
                'display_name' => 'Рабочая зона',
                'icon' => '💼',
                'color' => '#e3f2fd',
                'bounds' => ['x_min' => 0, 'x_max' => 600, 'y_min' => 0, 'y_max' => 400],
                'capacity' => 50,
                'amenities' => ['desks', 'monitors', 'chairs', 'power_outlets'],
            ],
            [
                'name' => 'meeting_room',
                'display_name' => 'Переговорная',
                'icon' => '🤝',
                'color' => '#fff3e0',
                'bounds' => ['x_min' => 620, 'x_max' => 800, 'y_min' => 0, 'y_max' => 200],
                'capacity' => 12,
                'amenities' => ['conference_table', 'whiteboard', 'projector', 'video_conf'],
            ],
            [
                'name' => 'brainstorm',
                'display_name' => 'Зона мозгового штурма',
                'icon' => '💡',
                'color' => '#f3e5f5',
                'bounds' => ['x_min' => 620, 'x_max' => 800, 'y_min' => 220, 'y_max' => 400],
                'capacity' => 15,
                'amenities' => ['whiteboards', 'sticky_notes', 'markers', 'comfortable_seating'],
            ],
            [
                'name' => 'break_room',
                'display_name' => 'Зона отдыха',
                'icon' => '🛋️',
                'color' => '#e8f5e9',
                'bounds' => ['x_min' => 0, 'x_max' => 300, 'y_min' => 420, 'y_max' => 580],
                'capacity' => 20,
                'amenities' => ['sofas', 'plants', 'games', 'relaxation_area'],
            ],
            [
                'name' => 'cafeteria',
                'display_name' => 'Столовая',
                'icon' => '🍽️',
                'color' => '#fff8e1',
                'bounds' => ['x_min' => 320, 'x_max' => 600, 'y_min' => 420, 'y_max' => 580],
                'capacity' => 30,
                'amenities' => ['tables', 'vending_machines', 'microwave', 'refrigerator'],
            ],
            [
                'name' => 'lounge',
                'display_name' => 'Лаунж зона',
                'icon' => '☕',
                'color' => '#fce4ec',
                'bounds' => ['x_min' => 620, 'x_max' => 800, 'y_min' => 420, 'y_max' => 580],
                'capacity' => 15,
                'amenities' => ['coffee_machine', 'comfortable_chairs', 'magazines', 'quiet_area'],
            ],
        ];

        foreach ($defaultZones as $zone) {
            self::updateOrCreate(
                ['name' => $zone['name']],
                $zone
            );
        }
    }
}
