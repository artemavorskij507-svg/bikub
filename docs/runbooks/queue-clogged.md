# Runbook: Очередь забита

## Симптомы
- Задачи в очереди не выполняются
- Виджет QueueHealthWidget показывает большое количество ожидающих задач
- Failed jobs > 5 за 10 минут
- Среднее время ожидания > 10 минут

## Диагностика

### 1. Проверить статус очереди
```bash
cd /var/www/glfbikube

# Проверить количество задач в очереди
php artisan queue:work --help
php artisan tinker --execute="
    echo 'Jobs in queue: ' . DB::table('jobs')->count() . "\n";
    echo 'Failed jobs: ' . DB::table('failed_jobs')->count() . "\n";
"
```

### 2. Проверить статус Horizon (если используется)
```bash
php artisan horizon:status

# Или через веб-интерфейс
# Открыть /admin/horizon
```

### 3. Проверить логи воркеров
```bash
tail -n 200 /var/www/glfbikube/storage/logs/laravel.log | grep -i "queue\|job"
```

### 4. Проверить настройки очереди
```bash
# Проверить драйвер очереди
php artisan tinker --execute="echo config('queue.default');"

# Проверить конфигурацию
cat config/queue.php | grep -A 10 "connections"
```

## Решение

### Вариант 1: Масштабирование воркеров (Horizon)
```bash
# Увеличить количество воркеров в config/horizon.php
# Затем перезапустить Horizon
php artisan horizon:terminate
php artisan horizon
```

### Вариант 2: Перезапуск воркеров
```bash
# Перезапустить все воркеры
php artisan queue:restart

# Запустить новый воркер
php artisan queue:work --tries=3 --timeout=60
```

### Вариант 3: Retry failed jobs
```bash
# Посмотреть failed jobs
php artisan queue:failed

# Повторить failed job по ID
php artisan queue:retry {job-id}

# Повторить все failed jobs
php artisan queue:retry all
```

### Вариант 4: Очистка старой очереди
```bash
# Удалить старые задачи (осторожно!)
php artisan tinker --execute="
    \$deleted = DB::table('jobs')
        ->where('created_at', '<', now()->subHours(24))
        ->delete();
    echo 'Deleted old jobs: ' . \$deleted . "\n";
"
```

### Вариант 5: Dead Letter Queue
```bash
# Переместить проблемные задачи в dead letter
php artisan tinker --execute="
    \$problemJobs = DB::table('jobs')
        ->where('attempts', '>', 3)
        ->get();
    
    foreach (\$problemJobs as \$job) {
        // Логировать или переместить в dead_letter таблицу
        // TODO: Реализовать dead_letter таблицу и механизм
    }
"
```

## Восстановление

1. **Перезапустить очередь:**
   ```bash
   php artisan queue:restart
   php artisan queue:work --verbose
   ```

2. **Проверить статус:**
   ```bash
   php artisan tinker --execute="
       echo 'Waiting jobs: ' . DB::table('jobs')->count() . "\n";
       echo 'Failed jobs: ' . DB::table('failed_jobs')->count() . "\n";
   "
   ```

3. **Мониторить выполнение:**
   ```bash
   # В реальном времени следить за логами
   tail -f /var/www/glfbikube/storage/logs/laravel.log | grep queue
   ```

## Мониторинг

- **Алерт:** Failed jobs > 5/10 мин → warn
- **Алерт:** Waiting jobs > 100 → warn
- **Алерт:** Average wait time > 10 min → warn

## Профилактика

- Настроить автоматическое масштабирование воркеров
- Регулярно очищать старые failed jobs
- Мониторить производительность воркеров
- Настроить retry политики для разных типов задач
- Использовать разные очереди для приоритетных задач


