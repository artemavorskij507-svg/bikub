# Інструкція для встановлення PHP 8.3 PostgreSQL розширення

## Проблема
```
could not find driver (Connection: pgsql, SQL: ...)
```

Це означає, що PHP не має встановленого розширення для PostgreSQL.

## Рішення

### 1. Встановити PHP 8.3 та розширення PostgreSQL:

```bash
sudo pacman -S php83 php83-apache php83-pgsql
```

### 2. Перевірити, що розширення встановлені:

```bash
php -m | grep pgsql
```

Має показати:
```
pdo_pgsql
pgsql
```

### 3. Перезапустити Apache:

```bash
sudo systemctl restart httpd
```

### 4. Перевірити в проекті:

```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
php artisan migrate
```

## Примітка

Файл конфігурації PHP 8.3 знаходиться в `/etc/php83/php.ini`.

Якщо після встановлення `php83-pgsql` розширення все ще не завантажуються, перевір:

```bash
ls -la /etc/php83/conf.d/ | grep pgsql
```

Якщо файлів немає, можливо потрібно розкоментувати в `/etc/php83/php.ini`:
- `extension=pdo_pgsql`
- `extension=pgsql`

