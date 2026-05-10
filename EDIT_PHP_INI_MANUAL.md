# Ручне редагування php.ini

## Проблема
Рядки 960 і 971 в `/etc/php83/php.ini` все ще мають активні:
- `extension=pdo_sqlite`
- `extension=sqlite3`

## Рішення 1: Редагування через nano/vim

```bash
sudo nano /etc/php83/php.ini
```

Знайдіть рядки 960 і 971, додайте `;` на початку:
- Змініть `extension=pdo_sqlite` на `;extension=pdo_sqlite`
- Змініть `extension=sqlite3` на `;extension=sqlite3`

Збережіть (Ctrl+O, Enter, Ctrl+X) та перезапустіть Apache:
```bash
sudo systemctl restart httpd
```

## Рішення 2: Через sed (якщо sudo працює)

```bash
sudo sed -i '960s/^extension=pdo_sqlite/;extension=pdo_sqlite/' /etc/php83/php.ini
sudo sed -i '971s/^extension=sqlite3/;extension=sqlite3/' /etc/php83/php.ini
sudo systemctl restart httpd
```

## Рішення 3: Копіювання та заміна

```bash
# Створити копію
sudo cp /etc/php83/php.ini /etc/php83/php.ini.backup

# Виконати заміну
sudo sed -i 's/^extension=pdo_sqlite$/;extension=pdo_sqlite/' /etc/php83/php.ini
sudo sed -i 's/^extension=sqlite3$/;extension=sqlite3/' /etc/php83/php.ini

# Перезапустити
sudo systemctl restart httpd
```

## Перевірка

```bash
# Перевірити рядки
sed -n '960p;971p' /etc/php83/php.ini

# Має бути:
# ;extension=pdo_sqlite
# ;extension=sqlite3

# Перевірити PHP
php -v  # Не повинно бути попереджень про SQLite
```

