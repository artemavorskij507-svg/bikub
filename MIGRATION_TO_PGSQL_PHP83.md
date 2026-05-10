# Миграция на PostgreSQL и PHP 8.3

## ✅ Выполнено автоматически

1. ✅ Обновлен `composer.json` - требование PHP изменено на `^8.3`
2. ✅ Обновлен `config/database.php`:
   - Удалена секция `sqlite`
   - По умолчанию установлен `pgsql`
3. ✅ Проверен `.env` - уже настроен на PostgreSQL

## ⚠️ Требуется выполнить вручную (требуются права sudo)

### 1. Отключить SQLite расширения PHP

Выполните следующие команды:

```bash
# Отключить pdo_sqlite
sudo sed -i 's/^extension=pdo_sqlite.so/;extension=pdo_sqlite.so/' /etc/php/conf.d/pdo_sqlite.ini

# Отключить sqlite3
sudo sed -i 's/^extension=sqlite3.so/;extension=sqlite3.so/' /etc/php/conf.d/sqlite3.ini

# Проверить результат
cat /etc/php/conf.d/pdo_sqlite.ini
cat /etc/php/conf.d/sqlite3.ini
```

Оба файла должны начинаться с `;extension=...` (закомментировано).

### 2. Убедиться что pdo_pgsql включен

```bash
# Проверить что расширение активно
php -m | grep pdo_pgsql

# Если не видно, включить в /etc/php/conf.d/pgsql.ini или /etc/php/php.ini
# Раскомментировать строку: extension=pdo_pgsql
```

### 3. Перезапустить веб-сервер

```bash
# Для Apache
sudo systemctl restart httpd

# Или для php-fpm
sudo systemctl restart php-fpm
```

### 4. Установить PHP 8.3

**Вариант А: Установка из AUR (рекомендуется для Arch/CachyOS)**

```bash
# Установить PHP 8.3 и необходимые расширения из AUR
yay -S php83 php83-pgsql php83-apache php83-gd php83-mbstring php83-xml php83-curl php83-zip php83-intl php83-bcmath php83-opcache

# Переключить Apache на PHP 8.3 (если используется mod_php)
# Обновить LoadModule в /etc/httpd/conf/httpd.conf или /etc/httpd/conf/extra/php8_module.conf
# Изменить: LoadModule php_module modules/libphp83.so

# Перезапустить Apache
sudo systemctl restart httpd

# Проверить версию
php -v  # Должна быть 8.3.x
```

**Вариант Б: Использовать PHP 8.4 (если 8.3 недоступен)**

PHP 8.4 обратно совместим с требованиями `^8.3` в composer.json. Если установка PHP 8.3 из AUR не работает, можно оставить PHP 8.4:

```bash
# Обновить composer.json чтобы разрешить PHP 8.4
# Изменить "php": "^8.3" на "php": "^8.3|^8.4"
# Или оставить "^8.3" - PHP 8.4 удовлетворяет этому требованию

# Проверить совместимость
composer check-platform-reqs
```

**Вариант В: Использовать Docker (для изоляции окружения)**

```bash
# Создать Dockerfile с PHP 8.3
# Или использовать Laravel Sail с PHP 8.3
```

### 5. Обновить зависимости Composer

После установки PHP 8.3:

```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'

# Проверить версию PHP
php -v  # Должна быть 8.3.x

# Обновить зависимости
composer update --no-interaction

# Очистить кеши Laravel
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
```

### 6. Проверить подключение к PostgreSQL

```bash
# Проверить что PostgreSQL работает
sudo systemctl status postgresql

# Проверить подключение через Laravel
php artisan tinker
>>> DB::connection()->getPdo();
# Должен вернуть объект PDO без ошибок
```

### 7. Обновить .env (если нужно)

Убедитесь что в `.env` указаны правильные настройки:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=glfbikube
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Удалите любые строки связанные с SQLite:**
- `DB_DATABASE=/path/to/database.sqlite`
- `DB_CONNECTION=sqlite`

## Проверка после миграции

```bash
# 1. Проверить что SQLite отключен
php -m | grep -i sqlite
# Не должно быть вывода

# 2. Проверить что PostgreSQL включен
php -m | grep pdo_pgsql
# Должно показать: pdo_pgsql

# 3. Проверить версию PHP
php -v
# Должна быть 8.3.x

# 4. Проверить работу Laravel
php artisan migrate:status
# Должно работать без ошибок
```

## Преимущества после миграции

✅ Полная совместимость с продакшеном (та же БД)  
✅ Возможность использовать специфичные для PostgreSQL функции:
   - `jsonb` для JSON полей
   - `uuid` для первичных ключей
   - Массивы PostgreSQL
   - Специфичные индексы

✅ Стабильность PHP 8.3 (более зрелая версия чем 8.4)

## Если возникнут проблемы

1. **Ошибка "could not find driver"**:
   - Убедитесь что `pdo_pgsql` включен: `php -m | grep pdo_pgsql`
   - Перезапустите веб-сервер

2. **Ошибка подключения к БД**:
   - Проверьте что PostgreSQL запущен: `sudo systemctl status postgresql`
   - Проверьте настройки в `.env`
   - Проверьте права доступа пользователя БД

3. **Проблемы с зависимостями Composer**:
   - Убедитесь что PHP 8.3 установлен: `php -v`
   - Очистите кеш Composer: `composer clear-cache`
   - Обновите зависимости: `composer update`

