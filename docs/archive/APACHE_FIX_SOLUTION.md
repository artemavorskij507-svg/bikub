# Решение проблемы с php artisan serve

## Проблема

Сервер `php artisan serve` отдаёт содержимое файла `routes/web.php` вместо выполнения PHP кода.

## Причина

Внутренний PHP веб-сервер (`php artisan serve`) использует файл `vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php` как роутер, который должен направлять все запросы на `public/index.php`. Однако в данном случае он отдаёт содержимое файла маршрутов, что указывает на проблему с конфигурацией.

## Решения

### Решение 1: Использовать Apache/Nginx (Рекомендуется)

#### Apache

1. Установить Apache и PHP модуль:
```bash
sudo apt-get install apache2 libapache2-mod-php
```

2. Создать конфигурацию виртуального хоста:
```bash
sudo nano /etc/apache2/sites-available/glfbikube.conf
```

```apache
<VirtualHost *:2222>
    ServerName localhost
    DocumentRoot /home/admin1/Проэкты /github/glfbikube/public

    <Directory /home/admin1/Проэкты /github/glfbikube/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/glfbikube-error.log
    CustomLog ${APACHE_LOG_DIR}/glfbikube-access.log combined
</VirtualHost>
```

3. Включить сайт и модули:
```bash
sudo a2ensite glfbikube
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx

1. Установить Nginx и PHP-FPM:
```bash
sudo apt-get install nginx php-fpm
```

2. Создать конфигурацию:
```bash
sudo nano /etc/nginx/sites-available/glfbikube
```

```nginx
server {
    listen 2222;
    server_name localhost;
    root /home/admin1/Проэкты /github/glfbikube/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

3. Включить сайт:
```bash
sudo ln -s /etc/nginx/sites-available/glfbikube /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

### Решение 2: Исправить php artisan serve

#### Проверить версию PHP:
```bash
php --version
php -m | grep -i "apache\|fpm"
```

#### Очистить все кеши:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan clear-compiled
composer dump-autoload
```

#### Перезапустить сервер с debug:
```bash
php artisan serve --host=0.0.0.0 --port=2222 --tries=0
```

### Решение 3: Использовать Docker (Для продакшн готовности)

#### docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "2222:80"
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - DB_CONNECTION=sqlite
      - DB_DATABASE=/var/www/html/database/database.sqlite

  nginx:
    image: nginx:alpine
    ports:
      - "2222:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
```

## Временное решение

Если нужно быстро протестировать без настройки веб-сервера:

```bash
# Использовать встроенный PHP сервер напрямую
php -S localhost:2222 -t public
```

Это запустит PHP сервер с корневой директорией в `public`, что должно правильно обрабатывать запросы.

## Проверка после исправления

1. Проверить главную страницу:
```bash
curl -s http://localhost:2222/ | head -20
```

Должен вернуться HTML, а не PHP код.

2. Проверить маршрут login:
```bash
curl -s http://localhost:2222/login
```

Должен быть редирект на `/admin/login`.

3. Проверить админ-панель:
```bash
curl -s http://localhost:2222/admin
```

Должна вернуться страница админ-панели Filament.

4. Проверить API:
```bash
curl -s http://localhost:2222/api/v1/health
```

Должен вернуться JSON ответ.

## Статус

- ❌ `php artisan serve` - не работает (отдаёт PHP код вместо выполнения)
- ⏳ Apache/Nginx - не настроены
- ⏳ Docker - не настроен
- ⏳ `php -S` - не протестирован

## Следующие шаги

1. Попробовать `php -S localhost:2222 -t public` для быстрого теста
2. Если не поможет - настроить Apache или Nginx
3. Протестировать все основные функции
4. Обновить документацию

## Дополнительные ресурсы

- [Laravel Deployment](https://laravel.com/docs/10.x/deployment)
- [PHP Built-in Web Server](https://www.php.net/manual/en/features.commandline.webserver.php)
- [Apache Virtual Hosts](https://httpd.apache.org/docs/2.4/vhosts/)
- [Nginx Server Blocks](https://www.nginx.com/resources/wiki/start/topics/examples/server_blocks/)

