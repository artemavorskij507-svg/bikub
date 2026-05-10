# Runbook: Vegvesen данные не обновляются

## Симптомы
- Виджеты TrafficStatsWidget и TrafficIncidentsTableWidget показывают устаревшие данные
- Последнее обновление > 75 минут назад
- Команды `vegvesen:ingest-incidents` и `vegvesen:ingest-travel-times` не выполняются

## Диагностика

### 1. Проверить статус планировщика
```bash
# Проверить, запущен ли cron
systemctl status crond  # или cron в зависимости от системы

# Проверить логи cron
tail -f /var/log/cron | grep vegvesen
```

### 2. Проверить последний запуск команд
```bash
cd /var/www/glfbikube
php artisan tinker --execute="
    \$last = \App\Models\ExternalDataCache::where('source', 'vegvesen')
        ->orderBy('fetched_at', 'desc')
        ->first();
    echo \$last ? \$last->fetched_at->diffForHumans() : 'No data found';
"
```

### 3. Проверить логи Laravel
```bash
tail -n 100 /var/www/glfbikube/storage/logs/laravel.log | grep -i vegvesen
```

### 4. Проверить конфигурацию планировщика
```bash
# Проверить, что команды зарегистрированы в Kernel.php
grep -A 5 "vegvesen:ingest" app/Console/Kernel.php
```

## Решение

### Вариант 1: Перезапуск планировщика
```bash
# Проверить, что планировщик видит команды
php artisan schedule:list | grep vegvesen

# Запустить команды вручную для проверки
php artisan vegvesen:ingest-incidents
php artisan vegvesen:ingest-travel-times

# Если команды работают, перезапустить планировщик
php artisan schedule:run
```

### Вариант 2: Перезапуск через cron
```bash
# Убедиться, что cron настроен правильно
crontab -l | grep "schedule:run"

# Если нет записи, добавить:
* * * * * cd /var/www/glfbikube && php artisan schedule:run >> /dev/null 2>&1
```

### Вариант 3: Проверка доступности API Vegvesen
```bash
# Проверить доступность CKAN API
curl -I "https://data.vegvesen.no/api/3/action/package_search?q=Trafikkmeldinger"

# Проверить DNS резолюцию
nslookup data.vegvesen.no
```

### Вариант 4: Очистка кэша
```bash
cd /var/www/glfbikube
php artisan cache:clear
php artisan config:clear
php artisan config:cache
```

### Вариант 5: Проверка ошибок парсинга
```bash
# Запустить команду с детальным логированием
php artisan vegvesen:ingest-incidents -v

# Проверить внешние данные в БД
php artisan tinker --execute="
    \$caches = \App\Models\ExternalDataCache::where('source', 'vegvesen')
        ->orderBy('fetched_at', 'desc')
        ->limit(5)
        ->get(['id', 'data_type', 'fetched_at', 'expires_at']);
    foreach (\$caches as \$c) {
        echo \$c->data_type . ': ' . \$c->fetched_at->format('Y-m-d H:i:s') . "\n";
    }
"
```

## Восстановление

1. **Перезапустить планировщик:**
   ```bash
   php artisan schedule:run
   ```

2. **Проверить, что данные обновились:**
   ```bash
   php artisan tinker --execute="
       echo 'Incidents: ' . \App\Models\TrafficIncident::count() . "\n";
       echo 'Travel Times: ' . \App\Models\TravelTime::count() . "\n";
   "
   ```

3. **Проверить виджеты в админке:**
   - Открыть `/admin`
   - Проверить виджеты TrafficStatsWidget и TrafficIncidentsTableWidget
   - Убедиться, что данные обновлены

## Мониторинг

- **Алерт:** Vegvesen ingest не запускался > 75 мин → warn
- **Критический алерт:** Vegvesen ingest не запускался > 2 ч → crit

## Профилактика

- Убедиться, что cron настроен и работает
- Регулярно проверять логи на ошибки парсинга
- Настроить мониторинг доступности API Vegvesen
- Проверять квоты API (если применимо)


