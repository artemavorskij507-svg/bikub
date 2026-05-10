# Ручная установка PHP 8.3 (если скрипты не работают)

## Проблема
Скрипт застрял на интерактивных вопросах yay о выборе `smtp-forwarder`.

## Решение 1: Прервать и использовать упрощенный скрипт

```bash
# 1. Прервать текущий процесс (Ctrl+C)

# 2. Запустить упрощенный скрипт
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
./INSTALL_PHP83_SIMPLE.sh
```

## Решение 2: Установка вручную по одному пакету

```bash
# 1. Установить dma (smtp-forwarder) из репозиториев
sudo pacman -S dma

# 2. Установить PHP 8.3 и расширения по одному
yay -S php83
yay -S php83-pdo
yay -S php83-pgsql
yay -S php83-apache
yay -S php83-gd
yay -S php83-mbstring
yay -S php83-xml
yay -S php83-curl
yay -S php83-zip
yay -S php83-intl
yay -S php83-bcmath
yay -S php83-opcache
yay -S php83-redis  # опционально
```

При каждом запросе выбирайте:
- Для `smtp-forwarder`: нажмите Enter (выбрать dma по умолчанию)
- Для "Пакеты для чистой сборки?": `Н` (Нет)
- Для "Показать изменения?": `Н` (Нет)

## Решение 3: Использовать PHP 8.4 (рекомендуется)

PHP 8.4 полностью совместим с `^8.3` и уже установлен:

```bash
# Просто проверьте что всё работает
php -v  # Должно быть 8.4.14
php -m | grep pdo_pgsql  # Должно показать: pdo_pgsql
php -m | grep -i sqlite  # Не должно быть вывода

# Обновить зависимости
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
composer update --no-interaction
php artisan optimize:clear
```

## После установки PHP 8.3

1. **Обновить конфигурацию Apache:**
```bash
# Найти файл конфигурации PHP
sudo grep -r "LoadModule php_module" /etc/httpd/

# Обычно это /etc/httpd/conf/httpd.conf или /etc/httpd/conf/extra/php8_module.conf
# Заменить:
# LoadModule php_module modules/libphp.so
# На:
# LoadModule php_module modules/libphp83.so
```

2. **Перезапустить Apache:**
```bash
sudo systemctl restart httpd
```

3. **Проверить версию:**
```bash
php -v  # Должно быть 8.3.x
php -m | grep pdo_pgsql
```

4. **Обновить зависимости:**
```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
composer update --no-interaction
php artisan optimize:clear
```

