# GeoZone & Routing Module

Модуль геозон и маршрутизации для GLF BiKube.

## Обзор

Модуль предоставляет:
- **GeoZone**: управление геозонами (круги, полигоны, bounding boxes)
- **Routing**: расчет маршрутов, расстояний, ETA с интеграцией OSRM/Mapbox
- **API endpoints**: для фронтенда и внешних интеграций
- **Filament Admin**: UI для управления зонами

## Архитектура

### GeoZone Service

Сервис `App\Services\Geo\GeoZoneService`:
- Кеширует активные зоны в Redis (TTL 300s)
- Определяет зоны для точки (point-in-polygon, point-in-circle)
- Определяет зоны для маршрута (intersection check)
- Метод `refreshCache()` для обновления кеша

### Routing Service

Сервис `App\Services\Routing\RoutingService`:
- Поддержка провайдеров: OSRM, Mapbox, internal (haversine fallback)
- Кеширование результатов (TTL 30s)
- Автоматический fallback при недоступности внешних сервисов

## Конфигурация

### config/routing.php

```php
'default_provider' => env('ROUTING_PROVIDER', 'osrm'),
'osrm' => [
    'url' => env('OSRM_URL', 'https://router.project-osrm.org'),
],
'mapbox' => [
    'token' => env('MAPBOX_TOKEN', null),
],
'avg_speeds' => [
    'car' => 40,   // km/h
    'bike' => 15,
    'walk' => 5,
],
```

### Переменные окружения

```env
ROUTING_PROVIDER=osrm
OSRM_URL=https://router.project-osrm.org
MAPBOX_TOKEN=your_token_here
ROUTING_CACHE_TTL=30
```

## API Endpoints

### GET /api/v1/geo/zones

Получить список активных зон (GeoJSON).

**Query параметры:**
- `active=1` - только активные
- `bbox=minlat,minlng,maxlat,maxlng` - фильтр по bounding box

**Пример:**
```bash
curl http://localhost:2244/api/v1/geo/zones
```

### POST /api/v1/geo/zone/contains

Проверить, какие зоны содержат точку.

**Payload:**
```json
{
  "lat": 68.43886,
  "lng": 17.42754
}
```

**Пример:**
```bash
curl -X POST http://localhost:2244/api/v1/geo/zone/contains \
  -H "Content-Type: application/json" \
  -d '{"lat":68.43886,"lng":17.42754}'
```

### POST /api/v1/route/estimate

Оценить маршрут между двумя точками.

**Payload:**
```json
{
  "from": {"lat": 68.43886, "lng": 17.42754},
  "to": {"lat": 68.44098, "lng": 17.37789},
  "transport": "car",
  "optimize": "fastest",
  "avoid_tolls": false,
  "service_type": "delivery"
}
```

**Пример:**
```bash
curl -X POST http://localhost:2244/api/v1/route/estimate \
  -H "Content-Type: application/json" \
  -d '{
    "from":{"lat":68.43886,"lng":17.42754},
    "to":{"lat":68.44098,"lng":17.37789},
    "transport":"car"
  }'
```

**Ответ:**
```json
{
  "success": true,
  "distance_km": 4.08,
  "duration_min": 9,
  "geometry": "{\"type\":\"LineString\",\"coordinates\":[...]}",
  "eta": "2025-11-21T18:30:00+00:00",
  "zones": [
    {
      "id": 1,
      "name": "Narvik City",
      "slug": "narvik-city",
      "meta": {"pricing_group": "city_center"}
    }
  ],
  "price_hint": {
    "subtotal": 85,
    "total": 85,
    "breakdown": [
      {"rule_name": "Base fee", "amount": 49},
      {"rule_name": "Per km", "amount": 36}
    ]
  },
  "provider": "osrm"
}
```

### POST /api/v1/route/matrix

Матрица расстояний/времени для множества точек.

**Payload:**
```json
{
  "points": [
    {"lat": 68.43886, "lng": 17.42754},
    {"lat": 68.44098, "lng": 17.37789},
    {"lat": 68.42010, "lng": 17.36520}
  ],
  "transport": "car"
}
```

## Управление зонами через Filament

### Создание зоны

1. Перейти в **Справочники и контент** → **Geo Zones**
2. Нажать **Create**
3. Заполнить:
   - **Name**: название зоны
   - **Slug**: уникальный идентификатор
   - **Type**: circle, polygon, bbox, multi
   - **Geometry**: GeoJSON объект
     - Для circle: `{"center":[lat,lng],"radius_m":60000}`
     - Для polygon: `{"type":"Polygon","coordinates":[[[lng,lat],...]]}`
   - **Meta**: JSON с метаданными (например, `{"pricing_group":"zone_a"}`)
   - **Priority**: приоритет (меньше = выше)
4. Сохранить

### Обновление кеша

В списке зон есть кнопка **"Обновить кеш зон"** - нажать для принудительного обновления Redis кеша.

## Добавление зон через Seeder

Запустить:
```bash
php artisan db:seed --class=GeoZonesNarvikSeeder
```

Seeder создает:
- **Narvik City** (polygon)
- **Ankenes** (circle, 15km)
- **Bjerkvik** (circle, 25km)
- **Narvik +60 km** (circle, 60km)

## Локальный OSRM (рекомендуется для production)

### Установка

```bash
# Docker
docker run -t -i -p 5000:5000 -v "${PWD}:/data" osrm/osrm-backend osrm-routed --algorithm mld /data/norway-latest.osrm

# Или скомпилировать из исходников
```

### Конфигурация

В `.env`:
```env
OSRM_URL=http://localhost:5000
```

## Тестирование

### Unit тесты

```bash
php artisan test tests/Unit/GeoZoneServiceTest.php
php artisan test tests/Unit/RoutingServiceFallbackTest.php
```

### Feature тесты

```bash
php artisan test tests/Feature/ApiRouteEstimateTest.php
```

## Производительность

### Кеширование

- **GeoZone cache**: Redis key `geo:active_zones`, TTL 300s
- **Route cache**: Redis key `route:hash(...)`, TTL 30s (настраивается)
- **Matrix cache**: Redis key `osrm:matrix:hash(...)`, TTL 60s

### Оптимизации

- Bounding box prefilter для быстрой проверки перед polygon intersection
- Использование PostGIS индексов для production (требует расширения)

## Безопасность

- Rate limiting на API endpoints (`throttle:api`)
- Валидация координат (lat: -90..90, lng: -180..180)
- Filament permissions: только SUPER-ADMIN / GEO-MANAGER могут создавать/редактировать зоны

## Troubleshooting

### OSRM недоступен

Система автоматически переключается на haversine fallback. Проверить логи:
```bash
tail -f storage/logs/laravel.log | grep -i osrm
```

### Зоны не находятся

1. Проверить, что зона активна (`is_active = true`)
2. Обновить кеш через Filament или:
   ```bash
   php artisan tinker
   >>> app(\App\Services\Geo\GeoZoneService::class)->refreshCache();
   ```

### Неправильные координаты в polygon

Убедиться, что GeoJSON формат корректен:
- Для Polygon: `[[[lng,lat],...]]` (внешнее кольцо)
- Координаты в порядке [longitude, latitude]

## Примеры использования

### Найти зоны для точки

```php
$service = app(\App\Services\Geo\GeoZoneService::class);
$zones = $service->findZonesForPoint(68.43886, 17.42754);
```

### Рассчитать маршрут

```php
$routing = app(\App\Services\Routing\RoutingService::class);
$from = new \App\Services\Routing\Point(68.43886, 17.42754);
$to = new \App\Services\Routing\Point(68.44098, 17.37789);
$result = $routing->route($from, $to, ['transport' => 'car']);
```

## Changelog

- 2025-11-21: Initial implementation with OSRM/Mapbox support and GeoZone management

