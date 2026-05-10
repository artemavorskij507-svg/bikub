<?php

namespace App\Services\Handyman;

use App\Models\ExecutorProfile;
use App\Models\HandymanOrderDetails;
use App\Models\HandymanService;
use App\Models\Order;
use Illuminate\Support\Collection;

class HandymanMatchingService
{
    /**
     * Найти подходящих мастеров для заказа.
     *
     * @return Collection<ExecutorProfile>
     */
    public function findCandidates(
        Order $order,
        HandymanOrderDetails $details,
        ?HandymanService $service,
        int $limit = 10
    ): Collection {
        // 1. Базовый фильтр: активные мастера
        $query = ExecutorProfile::query()
            ->where('is_active', true);

        // 2. Фильтр по skills (skills храним как json/array в ExecutorProfile)
        if ($service && ! empty($service->required_skills)) {
            foreach ($service->required_skills as $skill) {
                $query->whereJsonContains('skills', $skill);
            }
        }

        // 3. Фильтр по зоне/городу (минимально — совпадение city)
        // TODO: подстрой под реальное поле зоны/гео в ExecutorProfile
        // Пока пропускаем, так как в ExecutorProfile нет поля service_city

        // 4. TODO: учесть доступность по времени (слоты), расстояние, рейтинг, загрузку

        // 5. Сортировка по базовым признакам: рейтинг, кол-во выполненных заказов
        $query->orderByDesc('rating')
            ->orderByDesc('completed_orders_count');

        // TODO: добавить last_active_at, если поле будет добавлено

        return $query->limit($limit)->get();
    }
}
