# Інструкції для запуску міграцій та тестування Apache

## ✅ Виконано

1. **Створено .env файл** з базовою конфігурацією
2. **Створено test-apache.php** для діагностики Apache
3. **Виправлено Apache конфігурацію** (DocumentRoot)

## 📋 Налаштування PHP середовища

### Варіант 1: PHP в PATH
Якщо PHP встановлено, але не в PATH:
```bash
# Знайти PHP
which php8 php8.1 php8.2 php8.3 php81 php82 php83

# Або
find /usr -name "php" -type f 2>/dev/null

# Додати до PATH (якщо знайдено)
export PATH=$PATH:/шлях/до/php
```

### Варіант 2: Використання повного шляху
```bash
# Замінити 'php' на повний шлях до PHP
/повний/шлях/до/php artisan migrate
```

### Варіант 3: Через Apache
Якщо Apache налаштовано з PHP, міграції можна запустити через веб-інтерфейс або через Artisan Tinker.

## 🗄️ Запуск міграцій

### Перевірка статусу міграцій
```bash
php artisan migrate:status
```

### Запуск всіх pending міграцій
```bash
php artisan migrate
```

### Запуск з форсом (якщо є конфлікти)
```bash
php artisan migrate --force
```

### Відкат останньої міграції
```bash
php artisan migrate:rollback
```

### Відкат всіх міграцій
```bash
php artisan migrate:reset
```

## 🌐 Тестування Apache

### 1. Перевірка конфігурації
Файл: `apache-glfbikube.conf`
- Порт: 2244
- DocumentRoot: `/home/dima/Local server/public`

### 2. Запуск Apache сервісу

#### Для Arch Linux / CachyOS (httpd2):
```bash
# Перевірка статусу
sudo systemctl status httpd2

# Запуск
sudo systemctl start httpd2

# Автозапуск
sudo systemctl enable httpd2
```

#### Для Debian/Ubuntu (apache2):
```bash
# Перевірка статусу
sudo systemctl status apache2

# Запуск
sudo systemctl start apache2

# Автозапуск
sudo systemctl enable apache2
```

### 3. Додавання конфігурації
```bash
# Скопіювати конфігурацію
sudo cp apache-glfbikube.conf /etc/httpd2/conf/extra/glfbikube.conf

# Або для apache2
sudo cp apache-glfbikube.conf /etc/apache2/sites-available/glfbikube.conf

# Включити конфігурацію
# Для httpd2: додати Include в httpd2.conf
# Для apache2:
sudo a2ensite glfbikube.conf
sudo systemctl reload apache2
```

### 4. Тестування через браузер

#### Тестовий скрипт:
```
http://localhost:2244/test-apache.php
```

#### Основні endpoints:
- **Home**: `http://localhost:2244/`
- **Catalog**: `http://localhost:2244/catalog`
- **Admin Panel**: `http://localhost:2244/admin`
- **API Health**: `http://localhost:2244/api/v1/health`

### 5. Перевірка логів
```bash
# Помилки
sudo tail -f /var/log/httpd2/glfbikube-error.log

# Доступ
sudo tail -f /var/log/httpd2/glfbikube-access.log
```

## 🔧 Діагностика проблем

### Проблема: PHP не виконується
- Перевірити, чи встановлено PHP модуль для Apache
- Перевірити `php.ini` конфігурацію
- Перевірити права доступу до файлів

### Проблема: 404 Not Found
- Перевірити DocumentRoot в конфігурації
- Перевірити `.htaccess` файл
- Перевірити права доступу до директорій

### Проблема: 500 Internal Server Error
- Перевірити логи Apache
- Перевірити права доступу до storage та bootstrap/cache
- Перевірити .env файл

### Проблема: Database connection failed
- Перевірити шлях до database.sqlite в .env
- Перевірити права доступу до database/database.sqlite
- Перевірити чи встановлено PDO SQLite extension

## 📝 Примітки

- `.env` файл створено з базовою конфігурацією
- Потрібно згенерувати APP_KEY: `php artisan key:generate`
- Тестовий файл `test-apache.php` можна видалити після тестування
- Для продакшн потрібно встановити `APP_DEBUG=false` в .env

## ✅ Перевірка готовності

- [ ] .env файл створено
- [ ] APP_KEY згенеровано
- [ ] Apache конфігурація додана
- [ ] Apache сервіс запущено
- [ ] test-apache.php працює
- [ ] Міграції виконано
- [ ] API health check працює
- [ ] Публічний каталог працює
- [ ] Filament адмін-панель доступна

