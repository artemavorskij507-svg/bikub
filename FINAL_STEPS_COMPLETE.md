# Фінальні кроки для завершення міграції

## ✅ Виконано

1. ✅ **SQLite закоментовано** - попередження зникли
2. ✅ **PHP 8.3.21 працює** без попереджень
3. ✅ **PostgreSQL модулі працюють** (pdo_pgsql, pgsql)
4. ✅ **phpunit.xml** налаштовано на PostgreSQL
5. ✅ **tailwind.config.js** очищено від Next.js
6. ✅ **config/database.php** - SQLite видалено

## ⚠️ Потрібно виконати

### 1. Створити тестову БД (через su)

```bash
su -
# Введіть root пароль
createdb -U postgres -O dima glfbikube_test
exit
```

Або через sudo (якщо працює):
```bash
sudo -u postgres createdb -O dima glfbikube_test
```

### 2. Встановити DOM та tokenizer (якщо потрібно)

DOM та tokenizer можуть бути частиною базового пакета php83. Перевірте:

```bash
# Перевірити, чи завантажені
php -m | grep -E "^dom$|^tokenizer$"

# Якщо не завантажені, перевірити файли
ls -la /usr/lib/php83/modules/dom.so
ls -la /usr/lib/php83/modules/tokenizer.so
```

Якщо файли є, але не завантажені, додайте в конфігурацію:

```bash
su -
echo "extension=dom.so" > /etc/php83/conf.d/20-dom.ini
echo "extension=tokenizer.so" > /etc/php83/conf.d/20-tokenizer.ini
systemctl restart httpd
exit
```

### 3. Завершити composer install

```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
composer install --no-interaction
```

### 4. Очистити кеші Laravel

```bash
php artisan optimize:clear
```

### 5. Запустити тести

```bash
php artisan test
```

## 📋 Перевірка

Після виконання всіх кроків:

```bash
# PHP версія (без попереджень)
php -v

# Модулі
php -m | grep -E "pdo_pgsql|pgsql"  # Має бути
php -m | grep sqlite  # Має бути порожнім

# Laravel
php artisan --version
php artisan optimize:clear  # Має працювати

# Тести
php artisan test  # Має працювати на PostgreSQL
```

## 🎯 Очікуваний результат

- ✅ PHP 8.3.21 без попереджень про SQLite
- ✅ PostgreSQL модулі працюють
- ✅ SQLite повністю видалено
- ✅ Тести працюють на PostgreSQL
- ✅ Laravel команди працюють

