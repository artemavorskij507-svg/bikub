# 🎨 Фаза 2: Прототипы интерфейсов

**Агент**: @ux-architect  
**Дата**: 2026-03-31  
**Статус**: ✅ Завершено

---

## 📋 Обзор

Прототипы интерфейсов для 2D виртуального офиса с пиксельными агентами. Включает wireframes, mockups и прототипы交互 для всех основных экранов.

---

## 🖥️ Основные экраны

### 1. Главный экран офиса
**Назначение**: Основной экран с 2D виртуальным офисом

**Элементы**:
- Canvas 800x600 пикселей
- 6 офисных зон
- 162 пиксельных агента
- Миникарта (150x112 px)
- Тепловая карта
- Панель инструментов

**Wireframe**:
```
+--------------------------------------------------+
|  [Toolbar]                                       |
|  [Categories] [Zones] [Settings] [Help]          |
+--------------------------------------------------+
|                                                  |
|  +--------------------------------------------+  |
|  |                                            |  |
|  |              CANVAS 800x600                |  |
|  |                                            |  |
|  |    [Agent] [Agent] [Agent] [Agent]         |  |
|  |                                            |  |
|  |    [Zone: Workspace]                       |  |
|  |                                            |  |
|  |    [Zone: Meeting Room]                    |  |
|  |                                            |  |
|  +--------------------------------------------+  |
|                                                  |
|  [Minimap] [Heatmap] [Stats]                     |
+--------------------------------------------------+
```

---

### 2. Панель агента
**Назначение**: Информация об агенте и взаимодействие

**Элементы**:
- Аватар агента (32x32 px)
- Имя и описание
- Категория и зона
- Позиция (x, y)
- Кнопки действий
- История сообщений

**Wireframe**:
```
+--------------------------------------------------+
|  [Agent Avatar]  Frontend Developer              |
|                  Engineering                     |
|                  Workspace                       |
+--------------------------------------------------+
|  Position: (350, 100)                            |
|  Status: Active                                  |
+--------------------------------------------------+
|  [Move] [Chat] [Tasks] [Config]                  |
+--------------------------------------------------+
|  Recent Messages:                                |
|  - User: Привет!                                 |
|  - Agent: Привет! Как дела?                      |
|  - User: Нужна помощь с React                    |
+--------------------------------------------------+
|  [Send Message]                                  |
+--------------------------------------------------+
```

---

### 3. Список агентов
**Назначение**: Просмотр всех агентов

**Элементы**:
- Фильтры (категория, зона, статус)
- Поиск
- Список агентов
- Пагинация

**Wireframe**:
```
+--------------------------------------------------+
|  [Search: ____________] [Filter] [Sort]          |
+--------------------------------------------------+
|  Category: [All ▼]  Zone: [All ▼]  Status: [All▼]|
+--------------------------------------------------+
|  [Agent] Frontend Developer    Engineering       |
|         Workspace              Active            |
+--------------------------------------------------+
|  [Agent] Backend Architect     Engineering       |
|         Workspace              Active            |
+--------------------------------------------------+
|  [Agent] UI Designer           Design            |
|         Meeting Room           Active            |
+--------------------------------------------------+
|  [1] [2] [3] [4] [5] ... [10] [Next]             |
+--------------------------------------------------+
```

---

### 4. Список задач
**Назначение**: Управление задачами

**Элементы**:
- Фильтры (статус, приоритет, агент)
- Список задач
- Кнопки действий

**Wireframe**:
```
+--------------------------------------------------+
|  [Create Task]                                   |
+--------------------------------------------------+
|  Status: [All ▼]  Priority: [All ▼]  Agent: [All▼]|
+--------------------------------------------------+
|  [ ] Создать API для агентов                     |
|      Agent: Backend Architect                    |
|      Priority: High                              |
|      Status: In Progress                         |
|      Deadline: 2026-04-01                        |
+--------------------------------------------------+
|  [ ] Протестировать API                          |
|      Agent: API Tester                           |
|      Priority: Medium                            |
|      Status: Pending                             |
|      Deadline: 2026-04-02                        |
+--------------------------------------------------+
|  [1] [2] [3] [4] [5] ... [10] [Next]             |
+--------------------------------------------------+
```

---

### 5. Чат с агентом
**Назначение**: Диалог с агентом

**Элементы**:
- История сообщений
- Поле ввода
- Кнопка отправки

**Wireframe**:
```
+--------------------------------------------------+
|  [Agent Avatar]  Frontend Developer              |
|                  Online                          |
+--------------------------------------------------+
|                                                  |
|  User: Привет!                                   |
|                                                  |
|  Agent: Привет! Как дела?                        |
|                                                  |
|  User: Нужна помощь с React компонентом          |
|                                                  |
|  Agent: Конечно! Какой компонент нужно сделать?  |
|                                                  |
+--------------------------------------------------+
|  [Message: ____________________________] [Send]  |
+--------------------------------------------------+
```

---

### 6. Настройки офиса
**Назначение**: Конфигурация офиса

**Элементы**:
- Размеры офиса
- Настройки обновления
- Настройки анимации
- Настройки производительности

**Wireframe**:
```
+--------------------------------------------------+
|  Office Settings                                 |
+--------------------------------------------------+
|  Width: [800] px                                 |
|  Height: [600] px                                |
+--------------------------------------------------+
|  Update Interval: [3000] ms                      |
|  Movement Speed: [2] px/frame                    |
+--------------------------------------------------+
|  [✓] Enable Animations                           |
|  [✓] Enable Heatmap                              |
|  [✓] Enable Minimap                              |
+--------------------------------------------------+
|  Target Population: [170] agents                 |
+--------------------------------------------------+
|  [Save] [Cancel] [Reset]                         |
+--------------------------------------------------+
```

---

### 7. Дашборд статистики
**Назначение**: Мониторинг производительности

**Элементы**:
- Ключевые метрики
- Графики
- Алерты

**Wireframe**:
```
+--------------------------------------------------+
|  Dashboard                                       |
+--------------------------------------------------+
|  [Active Agents: 162] [Tasks: 25] [Messages: 150]|
+--------------------------------------------------+
|  Response Time: 150ms                            |
|  WebSocket Latency: 30ms                         |
|  FPS: 35                                         |
+--------------------------------------------------+
|  [Chart: Agent Activity]                         |
|  [Chart: Task Status]                            |
|  [Chart: Message Volume]                         |
+--------------------------------------------------+
|  Alerts:                                         |
|  - High CPU usage (85%)                          |
|  - Low memory (2GB free)                         |
+--------------------------------------------------+
```

---

### 8. Админ-панель
**Назначение**: Управление системой

**Элементы**:
- Управление агентами
- Управление категориями
- Управление зонами
- Управление пользователями
- Настройки системы

**Wireframe**:
```
+--------------------------------------------------+
|  Admin Panel                                     |
+--------------------------------------------------+
|  [Agents] [Categories] [Zones] [Users] [Settings]|
+--------------------------------------------------+
|  Agents Management                               |
+--------------------------------------------------+
|  [Create Agent] [Import] [Export]                |
+--------------------------------------------------+
|  [Table: Agents]                                 |
|  ID | Name | Category | Zone | Status | Actions  |
|  1  | Frontend | Engineering | Workspace | Active | [Edit] [Delete] |
|  2  | Backend | Engineering | Workspace | Active | [Edit] [Delete] |
+--------------------------------------------------+
|  [1] [2] [3] [4] [5] ... [10] [Next]             |
+--------------------------------------------------+
```

---

## 📱 Адаптивный дизайн

### Desktop (1920x1080)
```
+--------------------------------------------------+
|  Header                                          |
+--------------------------------------------------+
|  Sidebar | Main Content                         |
|          |                                       |
|          |  Canvas 800x600                       |
|          |                                       |
|          |  [Minimap] [Stats]                    |
+--------------------------------------------------+
|  Footer                                          |
+--------------------------------------------------+
```

### Tablet (1024x768)
```
+--------------------------------------------------+
|  Header                                          |
+--------------------------------------------------+
|  Canvas 600x450                                  |
|                                                  |
|  [Minimap] [Stats]                               |
+--------------------------------------------------+
|  [Sidebar Toggle]                                |
+--------------------------------------------------+
```

### Mobile (375x667)
```
+--------------------------------------------------+
|  Header [Menu]                                   |
+--------------------------------------------------+
|  Canvas 350x262                                  |
|                                                  |
|  [Stats]                                         |
+--------------------------------------------------+
|  [Agents] [Tasks] [Chat] [Settings]              |
+--------------------------------------------------+
```

---

## 🎨 Компоненты UI

### 1. Agent Card
```html
<div class="agent-card">
  <div class="agent-avatar">
    <img src="/storage/avatars/frontend-developer.png" alt="Frontend Developer">
  </div>
  <div class="agent-info">
    <h3>Frontend Developer</h3>
    <p class="category">Engineering</p>
    <p class="zone">Workspace</p>
    <p class="status active">Active</p>
  </div>
  <div class="agent-actions">
    <button class="btn btn-primary">Move</button>
    <button class="btn btn-secondary">Chat</button>
  </div>
</div>
```

### 2. Task Card
```html
<div class="task-card">
  <div class="task-header">
    <h3>Создать API для агентов</h3>
    <span class="priority high">High</span>
  </div>
  <div class="task-body">
    <p>Agent: Backend Architect</p>
    <p>Status: In Progress</p>
    <p>Deadline: 2026-04-01</p>
  </div>
  <div class="task-actions">
    <button class="btn btn-sm">Edit</button>
    <button class="btn btn-sm btn-danger">Delete</button>
  </div>
</div>
```

### 3. Message Bubble
```html
<div class="message user">
  <div class="message-content">
    <p>Привет!</p>
  </div>
  <div class="message-time">
    <span>10:30</span>
  </div>
</div>

<div class="message agent">
  <div class="message-avatar">
    <img src="/storage/avatars/frontend-developer.png" alt="Agent">
  </div>
  <div class="message-content">
    <p>Привет! Как дела?</p>
  </div>
  <div class="message-time">
    <span>10:31</span>
  </div>
</div>
```

### 4. Zone Label
```html
<div class="zone-label workspace" style="left: 0; top: 0; width: 600px; height: 400px;">
  <span class="zone-icon">💼</span>
  <span class="zone-name">Рабочая зона</span>
  <span class="zone-capacity">25/50</span>
</div>
```

### 5. Agent Avatar
```html
<div class="agent" style="left: 350px; top: 100px;">
  <img src="/storage/avatars/frontend-developer.png" alt="Frontend Developer" class="agent-avatar">
  <span class="agent-name">Frontend Developer</span>
</div>
```

---

## 🎭 Состояния UI

### 1. Loading
```html
<div class="loading">
  <div class="spinner"></div>
  <p>Loading agents...</p>
</div>
```

### 2. Empty
```html
<div class="empty">
  <div class="empty-icon">📭</div>
  <h3>No agents found</h3>
  <p>Create your first agent to get started</p>
  <button class="btn btn-primary">Create Agent</button>
</div>
```

### 3. Error
```html
<div class="error">
  <div class="error-icon">❌</div>
  <h3>Error loading agents</h3>
  <p>Please try again later</p>
  <button class="btn btn-secondary">Retry</button>
</div>
```

### 4. Success
```html
<div class="success">
  <div class="success-icon">✅</div>
  <h3>Agent created successfully</h3>
  <p>The agent has been added to the office</p>
</div>
```

---

## 🎨 Анимации

### 1. Появление агента
```css
@keyframes agent-appear {
  from {
    opacity: 0;
    transform: scale(0.5);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.agent {
  animation: agent-appear 0.3s ease-out;
}
```

### 2. Перемещение агента
```css
@keyframes agent-move {
  from {
    transform: translate(var(--from-x), var(--from-y));
  }
  to {
    transform: translate(var(--to-x), var(--to-y));
  }
}

.agent.moving {
  animation: agent-move 0.5s ease-out;
}
```

### 3. Пульсация активного агента
```css
@keyframes agent-pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(52, 152, 219, 0);
  }
}

.agent.active {
  animation: agent-pulse 2s infinite;
}
```

### 4. Появление уведомления
```css
@keyframes notification-slide-in {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.notification {
  animation: notification-slide-in 0.3s ease-out;
}
```

---

## 📊 Интерактивные элементы

### 1. Drag & Drop агентов
```javascript
// Перетаскивание агентов
agentElement.addEventListener('mousedown', (e) => {
  isDragging = true;
  startX = e.clientX - agentElement.offsetLeft;
  startY = e.clientY - agentElement.offsetTop;
});

document.addEventListener('mousemove', (e) => {
  if (isDragging) {
    const x = e.clientX - startX;
    const y = e.clientY - startY;
    agentElement.style.left = `${x}px`;
    agentElement.style.top = `${y}px`;
  }
});

document.addEventListener('mouseup', () => {
  if (isDragging) {
    isDragging = false;
    // Отправить API запрос на обновление позиции
    updateAgentPosition(agentId, x, y);
  }
});
```

### 2. Клик на агента
```javascript
agentElement.addEventListener('click', (e) => {
  e.stopPropagation();
  showAgentPanel(agentId);
});
```

### 3. Клик на зону
```javascript
zoneElement.addEventListener('click', (e) => {
  e.stopPropagation();
  showZoneInfo(zoneId);
});
```

### 4. Zoom & Pan
```javascript
// Масштабирование
canvas.addEventListener('wheel', (e) => {
  e.preventDefault();
  const scale = e.deltaY > 0 ? 0.9 : 1.1;
  canvas.style.transform = `scale(${scale})`;
});

// Перемещение
canvas.addEventListener('mousedown', (e) => {
  isPanning = true;
  startX = e.clientX - canvas.offsetLeft;
  startY = e.clientY - canvas.offsetTop;
});

document.addEventListener('mousemove', (e) => {
  if (isPanning) {
    const x = e.clientX - startX;
    const y = e.clientY - startY;
    canvas.style.left = `${x}px`;
    canvas.style.top = `${y}px`;
  }
});
```

---

## 📱 Мобильные жесты

### 1. Tap
```javascript
agentElement.addEventListener('tap', (e) => {
  showAgentPanel(agentId);
});
```

### 2. Double Tap
```javascript
agentElement.addEventListener('doubletap', (e) => {
  openAgentChat(agentId);
});
```

### 3. Long Press
```javascript
agentElement.addEventListener('press', (e) => {
  showAgentContextMenu(agentId);
});
```

### 4. Swipe
```javascript
canvas.addEventListener('swipe', (e) => {
  const direction = e.direction;
  if (direction === 'left') {
    // Переместить вид влево
  } else if (direction === 'right') {
    // Переместить вид вправо
  }
});
```

### 5. Pinch to Zoom
```javascript
canvas.addEventListener('pinch', (e) => {
  const scale = e.scale;
  canvas.style.transform = `scale(${scale})`;
});
```

---

## 🎨 Темы оформления

### 1. Светлая тема
```css
:root {
  --bg-primary: #FFFFFF;
  --bg-secondary: #F8F9FA;
  --text-primary: #212529;
  --text-secondary: #6C757D;
  --border-color: #DEE2E6;
  --shadow-color: rgba(0, 0, 0, 0.1);
}
```

### 2. Тёмная тема
```css
:root {
  --bg-primary: #212529;
  --bg-secondary: #343A40;
  --text-primary: #F8F9FA;
  --text-secondary: #ADB5BD;
  --border-color: #495057;
  --shadow-color: rgba(0, 0, 0, 0.3);
}
```

### 3. Контрастная тема
```css
:root {
  --bg-primary: #000000;
  --bg-secondary: #1A1A1A;
  --text-primary: #FFFFFF;
  --text-secondary: #CCCCCC;
  --border-color: #333333;
  --shadow-color: rgba(255, 255, 255, 0.1);
}
```

---

## 📊 Доступность (Accessibility)

### 1. ARIA labels
```html
<button aria-label="Move agent" class="btn">Move</button>
<div role="button" aria-label="Agent: Frontend Developer" class="agent">
  <img src="..." alt="Frontend Developer">
</div>
```

### 2. Keyboard navigation
```javascript
// Tab navigation
document.addEventListener('keydown', (e) => {
  if (e.key === 'Tab') {
    // Переключение между элементами
  }
});

// Arrow keys
document.addEventListener('keydown', (e) => {
  if (e.key === 'ArrowUp') {
    // Переместить агента вверх
  } else if (e.key === 'ArrowDown') {
    // Переместить агента вниз
  }
});
```

### 3. Screen reader support
```html
<div role="img" aria-label="Office canvas with 162 agents">
  <canvas id="office-canvas"></canvas>
</div>
```

### 4. High contrast mode
```css
@media (prefers-contrast: high) {
  :root {
    --bg-primary: #000000;
    --bg-secondary: #1A1A1A;
    --text-primary: #FFFFFF;
    --text-secondary: #CCCCCC;
    --border-color: #333333;
  }
}
```

### 5. Reduced motion
```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}
```

---

## 📚 Дополнительные ресурсы

- [API спецификация](PHASE2_API_SPECIFICATION.md)
- [Дизайн-система](PHASE2_DESIGN_SYSTEM.md)
- [Схема базы данных](PHASE2_DATABASE_SCHEMA.md)
- [Архитектурная документация](PHASE2_ARCHITECTURE_DOCUMENTATION.md)
- [Отчёт об аудите](PHASE1_AUDIT_REPORT.md)
- [Техническое задание](PHASE1_TECHNICAL_SPECIFICATION.md)

---

**Создано**: 2026-03-31  
**Агент**: @ux-architect  
**Статус**: ✅ Завершено
