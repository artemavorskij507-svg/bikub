# Фінальні кроки для завершення міграції

## ✅ Виконано

1. ✅ **SQLite закоментовано** - попередження зникли
2. ✅ **PHP 8.3.21 працює** без попереджень
3. ✅ **PostgreSQL модулі працюють** (pdo_pgsql, pgsql)
4. ✅ **DOM працює**
5. ✅ **xmlwriter працює**
6. ✅ **phpunit.xml** налаштовано на PostgreSQL
7. ✅ **tailwind.config.js** очищено від Next.js
8. ✅ **config/database.php** - SQLite видалено

## ⚠️ Потрібно виконати

### 1. Встановити php83-tokenizer (через su)

```bash
su -
pacman -U /home/dima/.cache/yay/php83/php83-tokenizer-8.3.21-1-x86_64.pkg.tar.zst
systemctl restart httpd
exit
```

### 2. Перевірити/встановити Composer

```bash
# Перевірити, чи є composer
which composer

# Якщо немає, встановити глобально
pacman -S composer

# Або встановити локально в проект
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

### 3. Створити тестову БД (через su)

```bash
su -
createdb -U postgres -O dima glfbikube_test
exit
```

### 4. Завершити налаштування

```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'

# Composer install
composer install --no-interaction
# або, якщо composer локальний:
php composer.phar install --no-interaction

# Очистити кеші
php artisan optimize:clear

# Запустити тести
php artisan test
```

## 📋 Перевірка

Після виконання всіх кроків:

```bash
# PHP модулі
php -m | grep -E "^dom$|^tokenizer$|^xmlwriter$"  # Має бути всі три
php -m | grep sqlite  # Має бути порожнім

# Laravel
php artisan --version
php artisan optimize:clear  # Має працювати

# Тести
php artisan test  # Має працювати на PostgreSQL
```

## 🎯 Очікуваний результат

- ✅ PHP 8.3.21 без попереджень
- ✅ PostgreSQL модулі працюють
- ✅ DOM, tokenizer, xmlwriter працюють
- ✅ SQLite повністю видалено
- ✅ Тести працюють на PostgreSQL
- ✅ Laravel команди працюють

