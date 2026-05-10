# Виправлення помилки PostgreSQL драйвера

## Проблема:
```
could not find driver (Connection: pgsql, ...)
error: target not found: php83-pgsql
```

## Рішення:

### Варіант 1: Якщо PHP 8.3 встановлено

Перевір, які пакети php83 встановлені:
```bash
pacman -Q | grep php83
```

Якщо є `php83`, але немає `php83-pgsql`, спробуй:
```bash
pacman -Ss php83 | grep pgsql
```

Можливі назви пакетів:
- `php83-pgsql`
- `php-pgsql` (якщо використовується загальний пакет)

### Варіант 2: Якщо PHP 8.3 не встановлено

Встанови PHP 8.3 та розширення:
```bash
sudo pacman -S php83 php83-apache
```

Потім встанови PostgreSQL розширення:
```bash
# Перевір доступні пакети
pacman -Ss php83 | grep -i pgsql

# Або спробуй загальний пакет
sudo pacman -S php-pgsql
```

### Перевірка після встановлення:

```bash
php -m | grep pgsql
php artisan migrate
```

## Примітка:

Проект використовує **PHP 8.3**.
Файл конфігурації PHP 8.3: `/etc/php83/php.ini`

