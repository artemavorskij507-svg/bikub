# 🔧 Настройка glfbikube.local

**Проблема:** Apache конфигурация указывает на `/var/www/glfbikube` вместо `/home/admin1/Проэкты /github/glfbikube`

## ✅ Решение

### 1. Обновите конфигурацию Apache:

```bash
sudo nano /etc/httpd2/conf/sites-enabled/glfbikube.conf
```

Измените пути:
```apache
DocumentRoot /home/admin1/Проэкты /github/glfbikube/public

<Directory /home/admin1/Проэкты /github/glfbikube/public>
```

### 2. Перезапустите Apache:

```bash
sudo systemctl restart httpd2
```

### 3. Проверьте что работает:

```bash
curl http://glfbikube.local/api/v1/health
```

---

## 📝 Альтернативный вариант

Вы можете просто использовать связку:

1. **PHP встроенный сервер** (уже работает):
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   Доступ: http://localhost:8000

2. **Или обновите .env** для правильного APP_URL:
   ```env
   APP_URL=http://glfbikube.local
   ```

---

*После обновления конфига сайт будет доступен по адресу: http://glfbikube.local*

