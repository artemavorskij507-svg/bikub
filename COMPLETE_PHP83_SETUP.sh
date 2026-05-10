#!/bin/bash
# Завершение установки и настройки PHP 8.3

set -e

echo "🚀 Завершение установки PHP 8.3"
echo ""

# Шаг 1: Установка недостающих пакетов PHP 8.3
echo "📦 Шаг 1: Установка недостающих пакетов PHP 8.3 из AUR..."

# Проверяем наличие dma
if ! pacman -Qi dma &>/dev/null; then
    echo "📧 Установка dma (smtp-forwarder)..."
    sudo pacman -S --noconfirm dma
fi

# Устанавливаем недостающие пакеты
PACKAGES=(
    "php83-apache"
    "php83-pgsql"
    "php83-gd"
    "php83-intl"
    "php83-mbstring"
    "php83-xml"
    "php83-zip"
    "php83-curl"
    "php83-bcmath"
    "php83-opcache"
)

echo "Установка пакетов: ${PACKAGES[*]}"
yay -S --batchinstall --noconfirm --answerclean All --answerdiff None --removemake "${PACKAGES[@]}"

echo ""
echo "✅ Пакеты PHP 8.3 установлены!"
echo ""

# Шаг 2: Создание symlink для CLI
echo "📋 Шаг 2: Создание symlink для команды php..."

# Ищем исполняемый файл PHP 8.3
PHP83_BIN=""

# Проверяем стандартные места
for path in "/usr/bin/php83" "/usr/bin/php8.3" "/usr/lib/php83/bin/php"; do
    if [ -f "$path" ] && [ -x "$path" ]; then
        PHP83_BIN="$path"
        break
    fi
done

# Если не найден, проверяем через pacman
if [ -z "$PHP83_BIN" ]; then
    PHP83_BIN=$(pacman -Ql php83 2>/dev/null | grep -E "/usr/bin/php[0-9]*$" | awk '{print $2}' | head -1)
fi

# Если все еще не найден, создаем wrapper скрипт
if [ -z "$PHP83_BIN" ]; then
    echo "⚠️  Исполняемый файл php83 не найден, создаем wrapper..."
    
    # Проверяем, есть ли php в системе (может быть от другого пакета)
    if command -v php83 &>/dev/null; then
        PHP83_BIN=$(command -v php83)
    else
        # Создаем wrapper скрипт
        sudo tee /usr/bin/php83 > /dev/null << 'EOF'
#!/bin/bash
# Wrapper для PHP 8.3
exec /usr/lib/php83/bin/php "$@"
EOF
        sudo chmod +x /usr/bin/php83
        PHP83_BIN="/usr/bin/php83"
        
        # Проверяем, существует ли /usr/lib/php83/bin/php
        if [ ! -f "/usr/lib/php83/bin/php" ]; then
            echo "❌ /usr/lib/php83/bin/php не найден. Проверяем установку php83..."
            pacman -Ql php83 | grep -E "bin/php" | head -5
            echo "Попробуйте переустановить: yay -S php83"
        fi
    fi
fi

# Удаляем старый symlink если есть
if [ -L "/usr/bin/php" ] || [ -f "/usr/bin/php" ]; then
    sudo rm -f /usr/bin/php
fi

# Создаем новый symlink
if [ -n "$PHP83_BIN" ] && [ -f "$PHP83_BIN" ]; then
    sudo ln -s "$PHP83_BIN" /usr/bin/php
    echo "✅ Symlink создан: /usr/bin/php -> $PHP83_BIN"
else
    echo "❌ Не удалось создать symlink. PHP83_BIN: $PHP83_BIN"
    exit 1
fi
echo ""

# Шаг 3: Настройка Apache
echo "📋 Шаг 3: Настройка Apache для PHP 8.3..."

# Ищем модуль PHP 8.3
PHP83_MODULE=$(find /usr/lib/httpd/modules -name "libphp83.so" 2>/dev/null | head -1)

if [ -z "$PHP83_MODULE" ]; then
    echo "⚠️  Модуль libphp83.so не найден, проверяем..."
    ls -la /usr/lib/httpd/modules/libphp*.so
    exit 1
fi

# Обновляем конфигурацию Apache
APACHE_CONF="/etc/httpd/conf/httpd.conf"
PHP_MODULE_CONF="/etc/httpd/conf/extra/php_module.conf"

if [ -f "$PHP_MODULE_CONF" ]; then
    echo "Обновление $PHP_MODULE_CONF..."
    sudo sed -i "s|LoadModule php_module modules/libphp.*\.so|LoadModule php_module modules/libphp83.so|" "$PHP_MODULE_CONF"
    sudo sed -i "s|LoadModule php_module modules/libphp8\.4\.so|LoadModule php_module modules/libphp83.so|" "$PHP_MODULE_CONF"
fi

if grep -q "LoadModule php_module" "$APACHE_CONF" 2>/dev/null; then
    echo "Обновление $APACHE_CONF..."
    sudo sed -i "s|LoadModule php_module modules/libphp.*\.so|LoadModule php_module modules/libphp83.so|" "$APACHE_CONF"
    sudo sed -i "s|LoadModule php_module modules/libphp8\.4\.so|LoadModule php_module modules/libphp83.so|" "$APACHE_CONF"
fi

echo "✅ Apache настроен"
echo ""

# Шаг 4: Настройка php.ini
echo "📋 Шаг 4: Настройка /etc/php83/php.ini..."

PHP_INI="/etc/php83/php.ini"

if [ ! -f "$PHP_INI" ]; then
    echo "❌ Файл $PHP_INI не найден!"
    exit 1
fi

# Включаем pdo_pgsql
sudo sed -i 's/^;extension=pdo_pgsql/extension=pdo_pgsql/' "$PHP_INI"
sudo sed -i 's/^;extension=pgsql/extension=pgsql/' "$PHP_INI"

# Включаем pdo_sqlite (для тестов)
sudo sed -i 's/^;extension=pdo_sqlite/extension=pdo_sqlite/' "$PHP_INI"
sudo sed -i 's/^;extension=sqlite3/extension=sqlite3/' "$PHP_INI"

# Удаляем дубликаты из conf.d
echo "Удаление дубликатов из /etc/php83/conf.d/..."
sudo rm -f /etc/php83/conf.d/pdo_sqlite.ini 2>/dev/null || true
sudo rm -f /etc/php83/conf.d/sqlite3.ini 2>/dev/null || true

echo "✅ php.ini настроен"
echo ""

# Шаг 5: Перезапуск Apache
echo "📋 Шаг 5: Перезапуск Apache..."
sudo systemctl restart httpd

echo ""
echo "✅ Установка завершена!"
echo ""
echo "📝 Финальная проверка:"
echo "   php -v"
echo "   php -m | grep pdo_pgsql"
echo "   php -m | grep pdo_sqlite"

