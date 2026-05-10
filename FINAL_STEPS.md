# Фінальні кроки для завершення міграції

## ✅ Що вже зроблено

1. ✅ `phpunit.xml` - налаштовано на PostgreSQL
2. ✅ `tailwind.config.js` - видалено посилання на Next.js
3. ✅ `config/database.php` - SQLite секція видалена
4. ✅ `.env` - налаштовано на `pgsql`
5. ✅ `npm install` - виконано

## 🔧 Що потрібно зробити вручну (з sudo)

### Крок 1: Встановити відсутні PHP розширення

```bash
# Перевірити, які розширення потрібні
php -m | grep -E "dom|tokenizer" || echo "Потрібно встановити"

# Встановити (якщо потрібно)
yay -S php83-tokenizer --noconfirm --batchinstall --answerclean All --answerdiff None --removemake
```

### Крок 2: Виправити php.ini (КРИТИЧНО)

```bash
# Закоментувати SQLite
sudo sed -i 's/^extension=pdo_sqlite$/;extension=pdo_sqlite/' /etc/php83/php.ini
sudo sed -i 's/^extension=sqlite3$/;extension=sqlite3/' /etc/php83/php.ini

# Розкоментувати pdo_pgsql
sudo sed -i 's/^;extension=pdo_pgsql$/extension=pdo_pgsql/' /etc/php83/php.ini

# Перезапустити Apache
sudo systemctl restart httpd
```

### Крок 3: Створити тестову БД

```bash
sudo -u postgres createdb -O dima glfbikube_test
```

### Крок 4: Завершити оновлення залежностей

```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'

# Composer (якщо не виконався)
composer install --no-interaction

# Очистити кеші Laravel
php artisan optimize:clear
```

### Крок 5: Перевірка

```bash
# PHP версія (не повинно бути попереджень про SQLite)
php -v

# Модулі (SQLite не повинен бути)
php -m | grep sqlite && echo "⚠️  SQLite все ще завантажено!" || echo "✅ SQLite не завантажено"

# Перевірка PostgreSQL модулів
php -m | grep -E "pdo_pgsql|pgsql"

# Запуск тестів
php artisan test
```

## 🚀 Альтернатива: Використати скрипт

```bash
cd '/home/dima/Стільниця/glfbikube (1-я копия)'
./REMOVE_SQLITE_COMPLETE.sh
```

## 📋 Очікуваний результат

Після виконання всіх кроків:

1. ✅ `php -v` → `PHP 8.3.21` (без попереджень)
2. ✅ `php artisan optimize:clear` → успішно виконується
3. ✅ `php artisan test` → тести проходять на PostgreSQL
4. ✅ `http://localhost:2244` → сайт працює
5. ✅ `http://localhost:2244/admin` → адмінка працює

## ⚠️ Важливі примітки

- **SQLite повністю видалено** з конфігурацій Laravel
- **Тести використовують PostgreSQL** (`glfbikube_test`)
- **PHP.ini потрібно виправити вручну** (через sudo)
- **DOM/tokenizer** можуть бути частиною `php83-xml` (вже встановлено)


