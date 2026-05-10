# 🔴 КРИТИЧЕСКАЯ ЗАДАЧА: Полная миграция на PHP 8.3

## Текущий статус

- ✅ yay установлен
- ✅ PHP 8.4 удален
- ⚠️ PHP 8.3 частично установлен (php83, php83-pdo)
- ⏳ Требуется установка остальных пакетов и настройка

## Выполнение миграции

### Вариант 1: Автоматический скрипт (рекомендуется)

```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
./COMPLETE_PHP83_SETUP.sh
```

Скрипт выполнит:
1. Установку всех недостающих пакетов PHP 8.3
2. Создание symlink для команды `php`
3. Настройку Apache
4. Настройку php.ini
5. Перезапуск Apache

### Вариант 2: Ручное выполнение

#### Шаг 1: Установка недостающих пакетов

```bash
# Установить dma (чтобы избежать интерактивных вопросов)
sudo pacman -S dma --noconfirm

# Установить все необходимые пакеты PHP 8.3
yay -S --batchinstall --noconfirm --answerclean All --answerdiff None \
    php83-apache \
    php83-pgsql \
    php83-gd \
    php83-intl \
    php83-mbstring \
    php83-xml \
    php83-zip \
    php83-curl \
    php83-bcmath \
    php83-opcache
```

#### Шаг 2: Создание symlink для CLI

```bash
# Найти исполняемый файл PHP 8.3
# Обычно он находится в одном из мест:
# - /usr/bin/php83 (если установлен)
# - /usr/lib/php83/bin/php (внутренний путь)

# Проверить наличие
ls -la /usr/bin/php83 /usr/lib/php83/bin/php 2>/dev/null

# Если найден, создать symlink
if [ -f "/usr/bin/php83" ]; then
    sudo ln -sf /usr/bin/php83 /usr/bin/php
elif [ -f "/usr/lib/php83/bin/php" ]; then
    # Создать wrapper
    sudo tee /usr/bin/php83 > /dev/null << 'EOF'
#!/bin/bash
exec /usr/lib/php83/bin/php "$@"
EOF
    sudo chmod +x /usr/bin/php83
    sudo ln -sf /usr/bin/php83 /usr/bin/php
fi
```

#### Шаг 3: Настройка Apache

```bash
# Найти модуль PHP 8.3
ls -la /usr/lib/httpd/modules/libphp83.so

# Обновить конфигурацию Apache
sudo sed -i 's|LoadModule php_module modules/libphp.*\.so|LoadModule php_module modules/libphp83.so|' /etc/httpd/conf/httpd.conf
sudo sed -i 's|LoadModule php_module modules/libphp.*\.so|LoadModule php_module modules/libphp83.so|' /etc/httpd/conf/extra/php_module.conf 2>/dev/null || true

# Перезапустить Apache
sudo systemctl restart httpd
```

#### Шаг 4: Настройка php.ini

```bash
PHP_INI="/etc/php83/php.ini"

# Включить pdo_pgsql
sudo sed -i 's/^;extension=pdo_pgsql/extension=pdo_pgsql/' "$PHP_INI"
sudo sed -i 's/^;extension=pgsql/extension=pgsql/' "$PHP_INI"

# Включить pdo_sqlite (для тестов)
sudo sed -i 's/^;extension=pdo_sqlite/extension=pdo_sqlite/' "$PHP_INI"
sudo sed -i 's/^;extension=sqlite3/extension=sqlite3/' "$PHP_INI"

# Удалить дубликаты из conf.d
sudo rm -f /etc/php83/conf.d/pdo_sqlite.ini 2>/dev/null
sudo rm -f /etc/php83/conf.d/sqlite3.ini 2>/dev/null
```

#### Шаг 5: Финальная проверка

```bash
# CLI версия
php -v  # Должно быть PHP 8.3.x

# Расширения
php -m | grep pdo_pgsql  # Должно показать: pdo_pgsql
php -m | grep pdo_sqlite  # Должно показать: pdo_sqlite (для тестов)

# Веб-версия
# Откройте: http://localhost/info.php
# Должно быть: PHP Version 8.3.x

# Composer
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
composer install

# Тесты
php artisan test
```

## Важные замечания

1. **Путь к php.ini**: После установки PHP 8.3 из AUR, php.ini находится в `/etc/php83/php.ini` (не `/etc/php/php.ini`)

2. **Исполняемый файл**: Если `/usr/bin/php83` не существует после установки, нужно создать wrapper или проверить установку пакета

3. **Модуль Apache**: После установки `php83-apache` модуль должен быть в `/usr/lib/httpd/modules/libphp83.so`

4. **pdo_sqlite**: Оставляем включенным для тестов, но удаляем дубликаты из `/etc/php83/conf.d/`

## Если что-то пошло не так

### Проблема: php -v все еще показывает 8.4

Решение:
```bash
# Проверить все symlink
ls -la /usr/bin/php*

# Удалить старый и создать новый
sudo rm /usr/bin/php
sudo ln -s /usr/bin/php83 /usr/bin/php
```

### Проблема: Apache не видит PHP 8.3

Решение:
```bash
# Проверить модуль
ls -la /usr/lib/httpd/modules/libphp83.so

# Проверить конфигурацию
grep -r "LoadModule php_module" /etc/httpd/

# Обновить вручную
sudo nano /etc/httpd/conf/httpd.conf
# Заменить: LoadModule php_module modules/libphp.so
# На: LoadModule php_module modules/libphp83.so
```

### Проблема: composer не работает

Решение:
```bash
# Обновить composer
composer self-update

# Переустановить зависимости
composer install
```

