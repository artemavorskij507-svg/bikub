# Статус міграції на PHP 8.3

## ✅ Виконано

1. **PHP 8.3.21 встановлено** (з AUR)
   - `php83-cli` встановлено та працює
   - Symlink `/usr/bin/php -> /usr/bin/php83` створено
   - CLI версія: `PHP 8.3.21`

2. **Модулі PHP 8.3**
   - `pdo_pgsql` - працює (є попередження про дублікати)
   - `pgsql` - працює (є попередження про дублікати)
   - `pdo_sqlite` - потрібно включити для тестів
   - `sqlite3` - потрібно включити для тестів

3. **Apache модуль**
   - `libphp83.so` знайдено: `/usr/lib/httpd/modules/libphp83.so`
   - Конфігурація: `/etc/httpd/conf/extra/php83-module.conf`

## ⚠️ Потрібно виправити

### 1. Дублікати в php.ini
**Проблема:** Модулі `pdo_pgsql` і `pgsql` завантажуються двічі:
- В `/etc/php83/php.ini` (рядки 959, 961): `extension=pdo_pgsql`, `extension=pgsql`
- В `/etc/php83/conf.d/20-pdo_pgsql.ini`: `extension=pdo_pgsql.so`
- В `/etc/php83/conf.d/20-pgsql.ini`: `extension=pgsql.so`

**Рішення:** Закоментувати рядки в `php.ini` (залишити тільки в `conf.d`)

### 2. SQLite для тестів
**Проблема:** `pdo_sqlite` і `sqlite3` закоментовані в `php.ini` (рядки 960, 971)

**Рішення:** Розкоментувати для тестів Laravel

### 3. Налаштування Apache
**Потрібно перевірити:**
- Чи є `LoadModule php_module modules/libphp83.so` в `httpd.conf` або `php83-module.conf`
- Чи включено `Include conf/extra/php83-module.conf` в `httpd.conf`

### 4. info.php для перевірки веб-версії
**Потрібно:** Скопіювати `public/info.php` в `/srv/http/info.php` (DocumentRoot Apache)

## 🚀 Наступні кроки

### Варіант 1: Автоматичне виправлення
```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
./FINAL_PHP83_SETUP.sh
```

### Варіант 2: Ручне виправлення

#### 1. Виправити php.ini
```bash
sudo sed -i 's/^extension=pdo_pgsql$/;extension=pdo_pgsql/' /etc/php83/php.ini
sudo sed -i 's/^extension=pgsql$/;extension=pgsql/' /etc/php83/php.ini
sudo sed -i 's/^;extension=pdo_sqlite$/extension=pdo_sqlite/' /etc/php83/php.ini
sudo sed -i 's/^;extension=sqlite3$/extension=sqlite3/' /etc/php83/php.ini
```

#### 2. Перевірити Apache
```bash
# Перевірити LoadModule
grep -r "LoadModule.*php.*libphp83" /etc/httpd/

# Перевірити Include
grep -r "Include.*php83-module" /etc/httpd/conf/httpd.conf

# Якщо немає, додати в httpd.conf:
sudo echo "LoadModule php_module modules/libphp83.so" >> /etc/httpd/conf/httpd.conf
sudo echo "Include conf/extra/php83-module.conf" >> /etc/httpd/conf/httpd.conf
```

#### 3. Створити info.php
```bash
sudo cp public/info.php /srv/http/info.php
sudo chmod 644 /srv/http/info.php
```

#### 4. Перезапустити Apache
```bash
sudo systemctl restart httpd
```

#### 5. Перевірити
```bash
# CLI
php -v
php -m | grep -E "pdo_pgsql|pgsql|pdo_sqlite|sqlite3"

# Веб
curl http://localhost/info.php | grep "PHP Version"
```

## 📋 Фінальна перевірка

Після виконання всіх кроків:

1. ✅ `php -v` → `PHP 8.3.21`
2. ✅ `php -m | grep pdo_pgsql` → `pdo_pgsql` (без попереджень)
3. ✅ `http://localhost/info.php` → `PHP Version 8.3.21`
4. ✅ `composer install` → успішно
5. ✅ `php artisan test` → тести проходять

## 📝 Примітки

- Попередження про `undefined symbol: pdo_parse_params` зникне після видалення дублікатів
- SQLite потрібен тільки для тестів Laravel, в продакшені використовується PostgreSQL
- Apache DocumentRoot: `/srv/http` (стандартний для Arch Linux)


