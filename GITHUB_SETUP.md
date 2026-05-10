# Інструкції для завантаження проекту на GitHub

## Статус

✅ Git репозиторій ініціалізовано  
✅ Всі файли додано до коміту  
✅ Створено коміт версії 0.1  
✅ Створено тег v0.1  
✅ README.md додано  
✅ Банк пам'яті включено в репозиторій  

## Налаштування SSH для GitHub

### Варіант 1: Використання існуючого SSH ключа

Якщо у вас вже є SSH ключ:
```bash
# Перевірити наявність ключа
ls -la ~/.ssh/id_*.pub

# Якщо ключ є, додайте його до GitHub:
# 1. Перейти на https://github.com/settings/keys
# 2. Натиснути "New SSH key"
# 3. Скопіювати вміст ~/.ssh/id_*.pub
```

### Варіант 2: Створити новий SSH ключ

```bash
# Створити новий SSH ключ
ssh-keygen -t ed25519 -C "freekill271@users.noreply.github.com"

# Запустити ssh-agent
eval "$(ssh-agent -s)"

# Додати ключ
ssh-add ~/.ssh/id_ed25519

# Скопіювати публічний ключ
cat ~/.ssh/id_ed25519.pub

# Додати ключ на GitHub:
# https://github.com/settings/keys -> New SSH key
```

### Варіант 3: Використання HTTPS з Personal Access Token

```bash
# Змінити remote на HTTPS
git remote set-url origin https://github.com/freekill271/kube.git

# Push з токеном (GitHub попросить username та token)
git push -u origin main
git push origin v0.1
```

## Виконання push

Після налаштування SSH або HTTPS:

```bash
cd "/home/dima/Local server"

# Push основної гілки
git push -u origin main

# Push тегу версії 0.1
git push origin v0.1
```

## Перевірка

```bash
# Перевірити статус
git status

# Перевірити коміти
git log --oneline

# Перевірити теги
git tag -l

# Перевірити remote
git remote -v
```

## Структура репозиторію

- ✅ Всі файли проекту
- ✅ Банк пам'яті (`memory-bank/`)
- ✅ Конфігурація Apache
- ✅ Документація
- ✅ README.md
- ✅ .gitignore

## Версія 0.1

Перша точка відновлення проекту:
- Всі міграції виконано
- Apache налаштовано
- API працює
- Проект готовий до розробки

