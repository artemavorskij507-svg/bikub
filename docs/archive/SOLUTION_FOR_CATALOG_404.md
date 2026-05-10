# РЕШЕНИЕ ПРОБЛЕМЫ /catalog 404

## Текущая ситуация

- ✅ Код Laravel работает корректно (CLI тест: 200)
- ✅ Маршрут `/catalog` зарегистрирован
- ✅ mod_rewrite активен в Apache
- ✅ `.htaccess` существует и настроен правильно
- ✅ `AllowOverride All` установлен
- ❌ `/catalog` возвращает 404 через Apache

## Анализ

**Проблема**: mod_rewrite из `.htaccess` НЕ применяется Apache. Даже несуществующие страницы возвращают 404 Apache, а не переписываются на `index.php`.

## Возможные причины

1. **Apache не может читать `.htaccess` через симлинк** - возможно, права на исходный файл
2. **Конфликт конфигов** - используется `/etc/httpd2/conf/sites-available/glfbikube.conf` вместо `/etc/httpd2/conf.d/glfbikube.conf`
3. **Проблема с путями** - DocumentRoot указывает на симлинк, а `.htaccess` может быть не виден

## Решение

### Вариант 1: Скопировать .htaccess напрямую в /var/www

```bash
sudo cp "/home/admin1/Проэкты /github/glfbikube/public/.htaccess" /var/www/glfbikube/public/.htaccess
sudo chown apache2:apache2 /var/www/glfbikube/public/.htaccess
sudo chmod 644 /var/www/glfbikube/public/.htaccess
sudo systemctl restart httpd2.service
```

### Вариант 2: Проверить, что конфиг обновлен правильно

```bash
# Убедиться, что используется правильный конфиг
sudo httpd2 -S | grep 2244

# Обновить оба конфига
sudo cp /etc/httpd2/conf.d/glfbikube.conf /etc/httpd2/conf/sites-available/glfbikube.conf
sudo httpd2 -t && sudo systemctl restart httpd2.service
```

### Вариант 3: Явно указать RewriteEngine в конфиге Apache

Добавить в `/etc/httpd2/conf/sites-available/glfbikube.conf`:

```apache
<Directory "/var/www/glfbikube/public">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
    
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [L]
    </IfModule>
</Directory>
```

