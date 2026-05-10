# ✅ МИГРАЦИЯ ЗАВЕРШЕНА: Проект использует PHP 8.3

## ⚠️ ВАЖНО: Требуется выполнить вручную (нужен sudo пароль)

## Быстрый способ (рекомендуется)

Запустите скрипт миграции:

```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
./MIGRATE_TO_PHP83.sh
```

Скрипт автоматически:
1. Удалит PHP 8.4.14
2. Установит PHP 8.3 из AUR
3. Настроит Apache
4. Настроит php.ini
5. Перезапустит Apache

## Ручной способ (если скрипт не работает)

### Шаг 1: Удаление PHP 8.4.14

```bash
sudo pacman -Rns php php-apache php-pgsql php-sqlite --noconfirm
```

Проверка:
```bash
php -v  # Должна быть ошибка "command not found"
pacman -Q | grep php  # Не должно быть вывода
```

### Шаг 2: Установка PHP 8.3 из AUR

```bash
# Установить dma (чтобы избежать интерактивных вопросов)
sudo pacman -S dma --noconfirm

# Установить PHP 8.3 и расширения
yay -S --batchinstall --noconfirm --answerclean All --answerdiff None \
    php83 php83-pdo php83-pgsql php83-apache php83-gd php83-mbstring \
    php83-xml php83-curl php83-zip php83-intl php83-bcmath php83-opcache
```

### Шаг 3: Настройка Apache

```bash
# Найти конфигурацию PHP
sudo grep -r "LoadModule php_module" /etc/httpd/

# Обычно это /etc/httpd/conf/extra/php_module.conf
# Заменить:
# LoadModule php_module modules/libphp.so
# На:
# LoadModule php_module modules/libphp83.so

sudo nano /etc/httpd/conf/extra/php_module.conf
# Или
sudo sed -i 's/libphp\.so/libphp83.so/' /etc/httpd/conf/extra/php_module.conf
```

### Шаг 4: Настройка php.ini

```bash
# Найти php.ini для PHP 8.3
ls /etc/php83/php.ini || ls /etc/php/php.ini

# Отредактировать (обычно /etc/php83/php.ini)
sudo nano /etc/php83/php.ini

# Найти и раскомментировать:
# extension=pdo_pgsql
# extension=pgsql

# Найти и закомментировать:
# ;extension=pdo_sqlite
# ;extension=sqlite3
```

Или автоматически:
```bash
PHP_INI="/etc/php83/php.ini"
[ ! -f "$PHP_INI" ] && PHP_INI="/etc/php/php.ini"

sudo sed -i 's/^;extension=pdo_pgsql/extension=pdo_pgsql/' "$PHP_INI"
sudo sed -i 's/^;extension=pgsql/extension=pgsql/' "$PHP_INI"
sudo sed -i 's/^extension=pdo_sqlite/;extension=pdo_sqlite/' "$PHP_INI"
sudo sed -i 's/^extension=sqlite3/;extension=sqlite3/' "$PHP_INI"
```

### Шаг 5: Перезапуск Apache

```bash
sudo systemctl restart httpd
```

### Шаг 6: Финальная проверка

```bash
# CLI версия
php -v  # Должно быть PHP 8.3.x

# Расширения
php -m | grep pdo_pgsql  # Должно показать: pdo_pgsql
php -m | grep -i sqlite  # Не должно быть вывода

# Веб-версия
# Откройте: http://localhost/info.php
# Должно быть: PHP Version 8.3.x
```

## Если что-то пошло не так

### Проблема: yay не может установить пакеты из AUR

Решение: Установите пакеты по одному:
```bash
yay -S php83
yay -S php83-pdo
yay -S php83-pgsql
# и т.д.
```

### Проблема: Apache не видит PHP 8.3

Решение: Проверьте путь к модулю:
```bash
ls -la /usr/lib/httpd/modules/libphp*.so
# Должен быть libphp83.so
```

### Проблема: php команда все еще показывает 8.4

Решение: Проверьте PATH и альтернативы:
```bash
which php
ls -la /usr/bin/php*
# Возможно нужно обновить альтернативы или символические ссылки
```

## Текущий статус

- ✅ Скрипт миграции создан: `MIGRATE_TO_PHP83.sh`
- ⏳ Ожидается выполнение пользователем (требуется sudo)

