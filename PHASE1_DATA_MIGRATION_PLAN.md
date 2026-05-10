# 📊 Фаза 1: План миграции данных

**Агент**: @data-engineer  
**Дата**: 2026-03-31  
**Статус**: ✅ Завершено

---

## 📋 Резюме

План миграции данных для интеграции 162 агентов из директории `agency-agents/` в систему 2D виртуального офиса. Миграция включает импорт агентов, категорий, офисных зон и конфигураций.

---

## 🎯 Цели миграции

1. ✅ Импортировать всех 162 агентов из `agency-agents/`
2. ✅ Создать 12 категорий агентов
3. ✅ Настроить 6 офисных зон
4. ✅ Мигрировать конфигурации
5. ✅ Обеспечить целостность данных

---

## 📊 Текущее состояние данных

### Исходные данные:
- **Агенты**: 162 файла `.md` в `agency-agents/`
- **Категории**: 12 папок (academic, design, engineering, etc.)
- **Конфигурации**: YAML frontmatter в файлах агентов

### Целевая структура:
- **Таблица agents**: 162 записи
- **Таблица categories**: 12 записей
- **Таблица office_zones**: 6 записей
- **Таблица agent_configs**: 162 записи

---

## 📁 Структура исходных данных

```
agency-agents/
├── academic/ (5 агентов)
├── design/ (8 агентов)
├── engineering/ (26 агентов)
├── game-development/ (20 агентов)
├── marketing/ (30 агентов)
├── paid-media/ (7 агентов)
├── product/ (5 агентов)
├── project-management/ (6 агентов)
├── sales/ (8 агентов)
├── spatial-computing/ (6 агентов)
├── specialized/ (28 агентов)
├── support/ (6 агентов)
└── testing/ (8 агентов)
```

---

## 📊 Схема целевой базы данных

### Таблица categories:
```sql
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    color VARCHAR(7) NOT NULL,
    icon VARCHAR(10) NOT NULL,
    sector_x_min INTEGER NOT NULL,
    sector_x_max INTEGER NOT NULL,
    sector_y_min INTEGER NOT NULL,
    sector_y_max INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### Таблица office_zones:
```sql
CREATE TABLE office_zones (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    icon VARCHAR(10) NOT NULL,
    color VARCHAR(7) NOT NULL,
    x_min INTEGER NOT NULL,
    x_max INTEGER NOT NULL,
    y_min INTEGER NOT NULL,
    y_max INTEGER NOT NULL,
    capacity INTEGER NOT NULL,
    amenities JSONB NOT NULL DEFAULT '[]',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### Таблица agents:
```sql
CREATE TABLE agents (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    category_id INTEGER REFERENCES categories(id),
    zone_id INTEGER REFERENCES office_zones(id),
    x_position INTEGER NOT NULL DEFAULT 0,
    y_position INTEGER NOT NULL DEFAULT 0,
    avatar VARCHAR(255),
    emoji VARCHAR(10),
    color VARCHAR(7),
    personality TEXT,
    mission TEXT,
    rules TEXT,
    workflow TEXT,
    deliverables TEXT,
    communication_style TEXT,
    success_metrics TEXT,
    source_file VARCHAR(500),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### Таблица agent_configs:
```sql
CREATE TABLE agent_configs (
    id SERIAL PRIMARY KEY,
    agent_id INTEGER REFERENCES agents(id),
    config_key VARCHAR(255) NOT NULL,
    config_value JSONB NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

---

## 📋 Процесс миграции

### Шаг 1: Парсинг файлов агентов
**Действие**: Извлечь данные из YAML frontmatter и Markdown контента  
**Инструмент**: Python/PHP скрипт  
**Вход**: Файлы `.md` из `agency-agents/`  
**Выход**: JSON объекты с данными агентов

**Извлекаемые поля**:
- `name` — имя агента
- `description` — описание
- `emoji` — эмодзи
- `color` — цвет
- `category` — категория (из папки)
- `personality` — личность
- `mission` — миссия
- `rules` — правила
- `workflow` — рабочий процесс
- `deliverables` — deliverables
- `communication_style` — стиль общения
- `success_metrics` — метрики успеха

---

### Шаг 2: Создание категорий
**Действие**: Создать 12 категорий на основе папок  
**Инструмент**: SQL INSERT  
**Вход**: Список папок  
**Выход**: 12 записей в таблице `categories`

**Данные категорий**:
```json
[
  {"name": "Academic", "slug": "academic", "color": "#3498DB", "icon": "📚"},
  {"name": "Design", "slug": "design", "color": "#9B59B6", "icon": "🎨"},
  {"name": "Engineering", "slug": "engineering", "color": "#2ECC71", "icon": "⚙️"},
  {"name": "Game Development", "slug": "game-development", "color": "#E74C3C", "icon": "🎮"},
  {"name": "Marketing", "slug": "marketing", "color": "#E84393", "icon": "📢"},
  {"name": "Paid Media", "slug": "paid-media", "color": "#F1C40F", "icon": "💰"},
  {"name": "Product", "slug": "product", "color": "#6366F1", "icon": "📦"},
  {"name": "Project Management", "slug": "project-management", "color": "#008080", "icon": "📋"},
  {"name": "Sales", "slug": "sales", "color": "#F39C12", "icon": "💼"},
  {"name": "Spatial Computing", "slug": "spatial-computing", "color": "#84CC16", "icon": "🖥️"},
  {"name": "Specialized", "slug": "specialized", "color": "#06B6D4", "icon": "🔧"},
  {"name": "Support", "slug": "support", "color": "#6B7280", "icon": "🛟"}
]
```

---

### Шаг 3: Создание офисных зон
**Действие**: Создать 6 офисных зон  
**Инструмент**: SQL INSERT  
**Вход**: Конфигурация из `config/agency-agents.php`  
**Выход**: 6 записей в таблице `office_zones`

**Данные зон**:
```json
[
  {"name": "Рабочая зона", "slug": "workspace", "icon": "💼", "color": "#e3f2fd", "x_min": 0, "x_max": 600, "y_min": 0, "y_max": 400, "capacity": 50},
  {"name": "Переговорная", "slug": "meeting_room", "icon": "🤝", "color": "#fff3e0", "x_min": 620, "x_max": 800, "y_min": 0, "y_max": 200, "capacity": 12},
  {"name": "Зона мозгового штурма", "slug": "brainstorm", "icon": "💡", "color": "#f3e5f5", "x_min": 620, "x_max": 800, "y_min": 220, "y_max": 400, "capacity": 15},
  {"name": "Зона отдыха", "slug": "break_room", "icon": "🛋️", "color": "#e8f5e9", "x_min": 0, "x_max": 300, "y_min": 420, "y_max": 580, "capacity": 20},
  {"name": "Столовая", "slug": "cafeteria", "icon": "🍽️", "color": "#fff8e1", "x_min": 320, "x_max": 600, "y_min": 420, "y_max": 580, "capacity": 30},
  {"name": "Лаунж", "slug": "lounge", "icon": "☕", "color": "#fce4ec", "x_min": 620, "x_max": 800, "y_min": 420, "y_max": 580, "capacity": 15}
]
```

---

### Шаг 4: Импорт агентов
**Действие**: Импортировать 162 агентов  
**Инструмент**: SQL INSERT  
**Вход**: JSON объекты агентов  
**Выход**: 162 записи в таблице `agents`

**Алгоритм**:
1. Прочитать каждый файл `.md`
2. Извлечь YAML frontmatter
3. Извлечь Markdown контент
4. Определить категорию по папке
5. Назначить случайную позицию в секторе категории
6. Создать запись в БД

---

### Шаг 5: Создание конфигураций
**Действие**: Создать конфигурации для агентов  
**Инструмент**: SQL INSERT  
**Вход**: Данные из YAML frontmatter  
**Выход**: 162 записи в таблице `agent_configs`

**Конфигурации**:
- `personality` — личность агента
- `mission` — миссия
- `rules` — правила
- `workflow` — рабочий процесс
- `deliverables` — deliverables
- `communication_style` — стиль общения
- `success_metrics` — метрики успеха

---

## 📊 Скрипты миграции

### Скрипт 1: Парсинг агентов (Python)
```python
import os
import yaml
import json
from pathlib import Path

def parse_agent_file(file_path):
    """Парсинг файла агента"""
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Извлечь YAML frontmatter
    if content.startswith('---'):
        parts = content.split('---', 2)
        if len(parts) >= 3:
            frontmatter = yaml.safe_load(parts[1])
            body = parts[2].strip()
            
            return {
                'name': frontmatter.get('name'),
                'description': frontmatter.get('description'),
                'emoji': frontmatter.get('emoji'),
                'color': frontmatter.get('color'),
                'body': body
            }
    
    return None

def parse_all_agents(base_path):
    """Парсинг всех агентов"""
    agents = []
    categories = {}
    
    for category_dir in Path(base_path).iterdir():
        if category_dir.is_dir():
            category_name = category_dir.name
            categories[category_name] = []
            
            for agent_file in category_dir.glob('*.md'):
                agent_data = parse_agent_file(agent_file)
                if agent_data:
                    agent_data['category'] = category_name
                    agent_data['source_file'] = str(agent_file)
                    categories[category_name].append(agent_data)
                    agents.append(agent_data)
    
    return agents, categories

# Запуск
agents, categories = parse_all_agents('agency-agents')
print(f"Найдено {len(agents)} агентов в {len(categories)} категориях")
```

---

### Скрипт 2: Создание категорий (SQL)
```sql
-- Создание категорий
INSERT INTO categories (name, slug, description, color, icon, sector_x_min, sector_x_max, sector_y_min, sector_y_max) VALUES
('Academic', 'academic', 'Academic and research specialists', '#3498DB', '📚', 0, 150, 0, 200),
('Design', 'design', 'UI/UX and visual design specialists', '#9B59B6', '🎨', 160, 310, 0, 200),
('Engineering', 'engineering', 'Software development and architecture', '#2ECC71', '⚙️', 320, 470, 0, 200),
('Game Development', 'game-development', 'Game design and development', '#E74C3C', '🎮', 480, 600, 0, 200),
('Marketing', 'marketing', 'Marketing and growth specialists', '#E84393', '📢', 0, 150, 210, 400),
('Paid Media', 'paid-media', 'Paid advertising specialists', '#F1C40F', '💰', 160, 310, 210, 400),
('Product', 'product', 'Product management and strategy', '#6366F1', '📦', 320, 470, 210, 400),
('Project Management', 'project-management', 'Project coordination and delivery', '#008080', '📋', 480, 600, 210, 400),
('Sales', 'sales', 'Sales and business development', '#F39C12', '💼', 0, 200, 0, 150),
('Spatial Computing', 'spatial-computing', 'AR/VR and spatial technology', '#84CC16', '🖥️', 0, 200, 160, 300),
('Specialized', 'specialized', 'Specialized domain experts', '#06B6D4', '🔧', 210, 400, 0, 150),
('Support', 'support', 'Support and operations', '#6B7280', '🛟', 410, 600, 0, 150);
```

---

### Скрипт 3: Создание офисных зон (SQL)
```sql
-- Создание офисных зон
INSERT INTO office_zones (name, slug, icon, color, x_min, x_max, y_min, y_max, capacity, amenities) VALUES
('Рабочая зона', 'workspace', '💼', '#e3f2fd', 0, 600, 0, 400, 50, '["desks", "monitors", "chairs", "power_outlets"]'),
('Переговорная', 'meeting_room', '🤝', '#fff3e0', 620, 800, 0, 200, 12, '["conference_table", "whiteboard", "projector", "video_conf"]'),
('Зона мозгового штурма', 'brainstorm', '💡', '#f3e5f5', 620, 800, 220, 400, 15, '["whiteboards", "sticky_notes", "markers", "comfortable_seating"]'),
('Зона отдыха', 'break_room', '🛋️', '#e8f5e9', 0, 300, 420, 580, 20, '["sofas", "plants", "games", "relaxation_area"]'),
('Столовая', 'cafeteria', '🍽️', '#fff8e1', 320, 600, 420, 580, 30, '["tables", "vending_machines", "microwave", "refrigerator"]'),
('Лаунж', 'lounge', '☕', '#fce4ec', 620, 800, 420, 580, 15, '["coffee_machine", "comfortable_chairs", "magazines", "quiet_area"]');
```

---

### Скрипт 4: Импорт агентов (PHP)
```php
<?php

use Illuminate\Support\Facades\DB;

function importAgents($agentsData) {
    foreach ($agentsData as $agentData) {
        // Получить ID категории
        $categoryId = DB::table('categories')
            ->where('slug', $agentData['category'])
            ->value('id');
        
        // Получить ID зоны (случайная зона)
        $zoneId = DB::table('office_zones')
            ->inRandomOrder()
            ->value('id');
        
        // Получить сектор категории
        $category = DB::table('categories')->find($categoryId);
        
        // Случайная позиция в секторе
        $xPosition = rand($category->sector_x_min, $category->sector_x_max);
        $yPosition = rand($category->sector_y_min, $category->sector_y_max);
        
        // Создать агента
        $agentId = DB::table('agents')->insertGetId([
            'name' => $agentData['name'],
            'slug' => $agentData['slug'],
            'description' => $agentData['description'],
            'category_id' => $categoryId,
            'zone_id' => $zoneId,
            'x_position' => $xPosition,
            'y_position' => $yPosition,
            'avatar' => $agentData['avatar'] ?? null,
            'emoji' => $agentData['emoji'] ?? null,
            'color' => $agentData['color'] ?? null,
            'source_file' => $agentData['source_file'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Создать конфигурации
        $configs = [
            'personality' => $agentData['personality'] ?? null,
            'mission' => $agentData['mission'] ?? null,
            'rules' => $agentData['rules'] ?? null,
            'workflow' => $agentData['workflow'] ?? null,
            'deliverables' => $agentData['deliverables'] ?? null,
            'communication_style' => $agentData['communication_style'] ?? null,
            'success_metrics' => $agentData['success_metrics'] ?? null,
        ];
        
        foreach ($configs as $key => $value) {
            if ($value) {
                DB::table('agent_configs')->insert([
                    'agent_id' => $agentId,
                    'config_key' => $key,
                    'config_value' => json_encode($value),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
```

---

## 📊 Валидация данных

### Чеклист валидации:
- [ ] Все 162 агента импортированы
- [ ] Все 12 категорий созданы
- [ ] Все 6 зон созданы
- [ ] Нет дубликатов slug
- [ ] Все foreign keys валидны
- [ ] Все JSON поля валидны
- [ ] Нет NULL в обязательных полях

### SQL запросы для валидации:
```sql
-- Проверить количество агентов
SELECT COUNT(*) FROM agents; -- Должно быть 162

-- Проверить количество категорий
SELECT COUNT(*) FROM categories; -- Должно быть 12

-- Проверить количество зон
SELECT COUNT(*) FROM office_zones; -- Должно быть 6

-- Проверить дубликаты slug
SELECT slug, COUNT(*) FROM agents GROUP BY slug HAVING COUNT(*) > 1;

-- Проверить NULL в обязательных полях
SELECT * FROM agents WHERE name IS NULL OR slug IS NULL OR category_id IS NULL;

-- Проверить foreign keys
SELECT a.* FROM agents a LEFT JOIN categories c ON a.category_id = c.id WHERE c.id IS NULL;
```

---

## 📊 План отката

### Шаг 1: Остановить приложение
```bash
php artisan down
```

### Шаг 2: Восстановить БД из бэкапа
```bash
psql -U postgres -d bikube < backup.sql
```

### Шаг 3: Запустить приложение
```bash
php artisan up
```

---

## 📊 Мониторинг миграции

### Метрики:
- Количество импортированных агентов
- Время выполнения миграции
- Количество ошибок
- Размер БД до/после

### Логирование:
```php
Log::info('Миграция агентов начата', ['total' => count($agents)]);
Log::info('Агент импортирован', ['name' => $agentData['name']]);
Log::error('Ошибка импорта агента', ['name' => $agentData['name'], 'error' => $e->getMessage()]);
Log::info('Миграция агентов завершена', ['imported' => $imported, 'errors' => $errors]);
```

---

## 📊 Тестирование миграции

### Тест 1: Проверка количества
```php
public function test_all_agents_imported()
{
    $this->assertEquals(162, DB::table('agents')->count());
}
```

### Тест 2: Проверка категорий
```php
public function test_all_categories_created()
{
    $this->assertEquals(12, DB::table('categories')->count());
}
```

### Тест 3: Проверка зон
```php
public function test_all_zones_created()
{
    $this->assertEquals(6, DB::table('office_zones')->count());
}
```

---

## 📚 Дополнительные ресурсы

- [Отчёт об аудите](PHASE1_AUDIT_REPORT.md)
- [Техническое задание](PHASE1_TECHNICAL_SPECIFICATION.md)
- [Архитектурная диаграмма](PHASE1_ARCHITECTURE_DIAGRAM.md)
- [User stories](PHASE1_USER_STORIES.md)

---

**Создано**: 2026-03-31  
**Агент**: @data-engineer  
**Статус**: ✅ Завершено
