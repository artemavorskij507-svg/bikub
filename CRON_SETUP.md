# Настройка планировщика Laravel для Vegvesen данных

## Автоматическое обновление данных Vegvesen

Планировщик настроен для автоматического обновления данных каждые 60 минут:

- **Трафик-инциденты (Trafikkmeldinger):** каждый час в :00
- **Время в пути (Reisetider):** каждый час в :05

## Настройка Cron на сервере

Для работы планировщика необходимо добавить задачу в crontab:

```bash
* * * * * cd /var/www/glfbikube && php artisan schedule:run >> /dev/null 2>&1
```

Или для конкретного пользователя:

```bash
sudo crontab -e -u www-data
```

И добавить строку:
```
* * * * * cd /var/www/glfbikube && php artisan schedule:run >> /dev/null 2>&1
```

## Проверка планировщика

Проверить список запланированных задач:
```bash
php artisan schedule:list
```

Запустить планировщик вручную (для тестирования):
```bash
php artisan schedule:run
```

## Логирование

Ошибки логируются в:
- `storage/logs/laravel.log`
- При сбое команды в лог пишется сообщение с префиксом `Vegvesen incidents ingestion failed` или `Vegvesen travel times ingestion failed`

## Настройки

Параметры команд можно изменить в файле `app/Console/Kernel.php`:
- Частота обновления (по умолчанию: каждый час)
- Смещение времени между командами (по умолчанию: 5 минут)
- Обработка ошибок и логирование

