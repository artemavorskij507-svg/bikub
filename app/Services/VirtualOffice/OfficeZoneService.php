<?php

namespace App\Services\VirtualOffice;

use App\Models\VirtualOffice\OfficeZone;
use App\Models\VirtualOffice\Agent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OfficeZoneService
{
    /**
     * Получить все зоны
     */
    public function getAllZones(): Collection
    {
        return OfficeZone::withCount(['agents', 'activeAgents'])->get();
    }

    /**
     * Получить зону по ID
     */
    public function getZone(int $id): ?OfficeZone
    {
        return OfficeZone::with(['agents.category', 'activeAgents.category'])
            ->withCount(['agents', 'activeAgents'])
            ->find($id);
    }

    /**
     * Получить зону по slug
     */
    public function getZoneBySlug(string $slug): ?OfficeZone
    {
        return OfficeZone::with(['agents.category', 'activeAgents.category'])
            ->withCount(['agents', 'activeAgents'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Создать новую зону
     */
    public function createZone(array $data): OfficeZone
    {
        $zone = OfficeZone::create($data);

        Log::info('Офисная зона создана', [
            'zone_id' => $zone->id,
            'name' => $zone->name,
            'slug' => $zone->slug,
        ]);

        return $zone->loadCount(['agents', 'activeAgents']);
    }

    /**
     * Обновить зону
     */
    public function updateZone(int $id, array $data): ?OfficeZone
    {
        $zone = OfficeZone::find($id);

        if (!$zone) {
            return null;
        }

        $zone->update($data);

        Log::info('Офисная зона обновлена', [
            'zone_id' => $zone->id,
            'name' => $zone->name,
        ]);

        return $zone->loadCount(['agents', 'activeAgents']);
    }

    /**
     * Удалить зону
     */
    public function deleteZone(int $id): bool
    {
        $zone = OfficeZone::find($id);

        if (!$zone) {
            return false;
        }

        // Проверить, есть ли агенты в зоне
        if ($zone->agents()->count() > 0) {
            Log::warning('Попытка удалить зону с агентами', [
                'zone_id' => $id,
                'name' => $zone->name,
                'agent_count' => $zone->agents()->count(),
            ]);
            return false;
        }

        $zone->delete();

        Log::info('Офисная зона удалена', [
            'zone_id' => $id,
            'name' => $zone->name,
        ]);

        return true;
    }

    /**
     * Получить агентов в зоне
     */
    public function getZoneAgents(int $zoneId): Collection
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return collect();
        }

        return $zone->agents()->with(['category'])->get();
    }

    /**
     * Получить активных агентов в зоне
     */
    public function getZoneActiveAgents(int $zoneId): Collection
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return collect();
        }

        return $zone->activeAgents()->with(['category'])->get();
    }

    /**
     * Проверить, находится ли точка в зоне
     */
    public function isPointInZone(int $zoneId, int $x, int $y): bool
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return false;
        }

        return $zone->containsPoint($x, $y);
    }

    /**
     * Получить зону по координатам
     */
    public function getZoneByCoordinates(int $x, int $y): ?OfficeZone
    {
        return OfficeZone::where('x_min', '<=', $x)
            ->where('x_max', '>=', $x)
            ->where('y_min', '<=', $y)
            ->where('y_max', '>=', $y)
            ->first();
    }

    /**
     * Получить статистику зон
     */
    public function getZoneStats(): array
    {
        $zones = OfficeZone::withCount(['agents', 'activeAgents'])->get();

        $stats = $zones->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'slug' => $zone->slug,
                'icon' => $zone->icon,
                'color' => $zone->color,
                'capacity' => $zone->capacity,
                'total_agents' => $zone->agents_count,
                'active_agents' => $zone->active_agents_count,
                'available_spots' => $zone->getAvailableSpots(),
                'is_full' => $zone->isFull(),
                'occupancy_rate' => $zone->capacity > 0
                    ? round(($zone->active_agents_count / $zone->capacity) * 100, 2)
                    : 0,
            ];
        });

        return [
            'zones' => $stats,
            'total_zones' => $zones->count(),
            'total_capacity' => $zones->sum('capacity'),
            'total_agents' => $zones->sum('agents_count'),
            'total_active_agents' => $zones->sum('active_agents_count'),
        ];
    }

    /**
     * Получить центр зоны
     */
    public function getZoneCenter(int $zoneId): ?array
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return null;
        }

        return $zone->getCenter();
    }

    /**
     * Получить размеры зоны
     */
    public function getZoneSize(int $zoneId): ?array
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return null;
        }

        return $zone->getSize();
    }

    /**
     * Проверить, заполнена ли зона
     */
    public function isZoneFull(int $zoneId): bool
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return false;
        }

        return $zone->isFull();
    }

    /**
     * Получить количество свободных мест в зоне
     */
    public function getAvailableSpots(int $zoneId): int
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return 0;
        }

        return $zone->getAvailableSpots();
    }

    /**
     * Получить зоны с свободными местами
     */
    public function getZonesWithAvailableSpots(): Collection
    {
        return OfficeZone::withCount('activeAgents')
            ->get()
            ->filter(function ($zone) {
                return !$zone->isFull();
            })
            ->values();
    }

    /**
     * Получить заполненные зоны
     */
    public function getFullZones(): Collection
    {
        return OfficeZone::withCount('activeAgents')
            ->get()
            ->filter(function ($zone) {
                return $zone->isFull();
            })
            ->values();
    }

    /**
     * Получить зоны по удобствам
     */
    public function getZonesByAmenity(string $amenity): Collection
    {
        return OfficeZone::whereJsonContains('amenities', $amenity)->get();
    }

    /**
     * Получить зоны по вместимости
     */
    public function getZonesByCapacity(int $minCapacity, ?int $maxCapacity = null): Collection
    {
        $query = OfficeZone::where('capacity', '>=', $minCapacity);

        if ($maxCapacity !== null) {
            $query->where('capacity', '<=', $maxCapacity);
        }

        return $query->get();
    }

    /**
     * Получить случайную зону
     */
    public function getRandomZone(): ?OfficeZone
    {
        return OfficeZone::inRandomOrder()->first();
    }

    /**
     * Получить зоны рядом с точкой
     */
    public function getZonesNearPoint(int $x, int $y, int $radius = 100): Collection
    {
        return OfficeZone::where(function ($query) use ($x, $y, $radius) {
            $query->whereBetween('x_min', [$x - $radius, $x + $radius])
                  ->orWhereBetween('x_max', [$x - $radius, $x + $radius])
                  ->orWhereBetween('y_min', [$y - $radius, $y + $radius])
                  ->orWhereBetween('y_max', [$y - $radius, $y + $radius]);
        })->get();
    }

    /**
     * Получить пересекающиеся зоны
     */
    public function getOverlappingZones(int $zoneId): Collection
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return collect();
        }

        return OfficeZone::where('id', '!=', $zoneId)
            ->where(function ($query) use ($zone) {
                $query->where('x_min', '<', $zone->x_max)
                      ->where('x_max', '>', $zone->x_min)
                      ->where('y_min', '<', $zone->y_max)
                      ->where('y_max', '>', $zone->y_min);
            })
            ->get();
    }

    /**
     * Получить зоны по цвету
     */
    public function getZonesByColor(string $color): Collection
    {
        return OfficeZone::where('color', $color)->get();
    }

    /**
     * Получить зоны по иконке
     */
    public function getZonesByIcon(string $icon): Collection
    {
        return OfficeZone::where('icon', $icon)->get();
    }
}
