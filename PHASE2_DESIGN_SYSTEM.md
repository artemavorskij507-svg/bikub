# 🎨 Фаза 2: Дизайн-система компонентов

**Агент**: @ui-designer  
**Дата**: 2026-03-31  
**Статус**: ✅ Завершено

---

## 📋 Обзор

Дизайн-система для 2D виртуального офиса с пиксельными агентами. Включает цветовую палитру, типографику, компоненты UI, иконки и аватары агентов.

---

## 🎨 Цветовая палитра

### Основные цвета:
```css
:root {
  /* Primary */
  --color-primary: #3498DB;
  --color-primary-light: #5DADE2;
  --color-primary-dark: #2980B9;

  /* Secondary */
  --color-secondary: #9B59B6;
  --color-secondary-light: #BB8FCE;
  --color-secondary-dark: #8E44AD;

  /* Success */
  --color-success: #2ECC71;
  --color-success-light: #58D68D;
  --color-success-dark: #27AE60;

  /* Warning */
  --color-warning: #F1C40F;
  --color-warning-light: #F4D03F;
  --color-warning-dark: #F39C12;

  /* Danger */
  --color-danger: #E74C3C;
  --color-danger-light: #EC7063;
  --color-danger-dark: #C0392B;

  /* Info */
  --color-info: #3498DB;
  --color-info-light: #5DADE2;
  --color-info-dark: #2980B9;

  /* Neutral */
  --color-white: #FFFFFF;
  --color-black: #000000;
  --color-gray-100: #F8F9FA;
  --color-gray-200: #E9ECEF;
  --color-gray-300: #DEE2E6;
  --color-gray-400: #CED4DA;
  --color-gray-500: #ADB5BD;
  --color-gray-600: #6C757D;
  --color-gray-700: #495057;
  --color-gray-800: #343A40;
  --color-gray-900: #212529;
}
```

### Цвета категорий:
```css
:root {
  --category-academic: #3498DB;
  --category-design: #9B59B6;
  --category-engineering: #2ECC71;
  --category-game-development: #E74C3C;
  --category-marketing: #E84393;
  --category-paid-media: #F1C40F;
  --category-product: #6366F1;
  --category-project-management: #008080;
  --category-sales: #F39C12;
  --category-spatial-computing: #84CC16;
  --category-specialized: #06B6D4;
  --category-support: #6B7280;
}
```

### Цвета зон:
```css
:root {
  --zone-workspace: #e3f2fd;
  --zone-meeting-room: #fff3e0;
  --zone-brainstorm: #f3e5f5;
  --zone-break-room: #e8f5e9;
  --zone-cafeteria: #fff8e1;
  --zone-lounge: #fce4ec;
}
```

---

## 📝 Типографика

### Шрифты:
```css
:root {
  --font-family-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-family-mono: 'Fira Code', 'Consolas', 'Monaco', monospace;
  --font-family-pixel: 'Press Start 2P', cursive;
}
```

### Размеры шрифтов:
```css
:root {
  --font-size-xs: 0.75rem;    /* 12px */
  --font-size-sm: 0.875rem;   /* 14px */
  --font-size-base: 1rem;     /* 16px */
  --font-size-lg: 1.125rem;   /* 18px */
  --font-size-xl: 1.25rem;    /* 20px */
  --font-size-2xl: 1.5rem;    /* 24px */
  --font-size-3xl: 1.875rem;  /* 30px */
  --font-size-4xl: 2.25rem;   /* 36px */
  --font-size-5xl: 3rem;      /* 48px */
}
```

### Веса шрифтов:
```css
:root {
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;
}
```

### Высота строки:
```css
:root {
  --line-height-tight: 1.25;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.75;
}
```

---

## 📦 Компоненты UI

### Кнопки:
```css
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
  border-radius: 0.375rem;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background-color: var(--color-primary);
  color: var(--color-white);
}

.btn-primary:hover {
  background-color: var(--color-primary-dark);
}

.btn-secondary {
  background-color: var(--color-secondary);
  color: var(--color-white);
}

.btn-success {
  background-color: var(--color-success);
  color: var(--color-white);
}

.btn-danger {
  background-color: var(--color-danger);
  color: var(--color-white);
}

.btn-outline {
  background-color: transparent;
  border-color: var(--color-gray-300);
  color: var(--color-gray-700);
}

.btn-outline:hover {
  background-color: var(--color-gray-100);
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: var(--font-size-sm);
}

.btn-lg {
  padding: 0.75rem 1.5rem;
  font-size: var(--font-size-lg);
}
```

### Карточки:
```css
.card {
  background-color: var(--color-white);
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  padding: 1rem;
  border-bottom: 1px solid var(--color-gray-200);
}

.card-body {
  padding: 1rem;
}

.card-footer {
  padding: 1rem;
  border-top: 1px solid var(--color-gray-200);
  background-color: var(--color-gray-100);
}
```

### Формы:
```css
.form-group {
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-gray-700);
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: var(--font-size-base);
  line-height: var(--line-height-normal);
  color: var(--color-gray-900);
  background-color: var(--color-white);
  border: 1px solid var(--color-gray-300);
  border-radius: 0.375rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-control::placeholder {
  color: var(--color-gray-400);
}

.form-control:disabled {
  background-color: var(--color-gray-100);
  cursor: not-allowed;
}
```

### Модальные окна:
```css
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-dialog {
  background-color: var(--color-white);
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid var(--color-gray-200);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-body {
  padding: 1rem;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid var(--color-gray-200);
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
```

### Уведомления:
```css
.alert {
  padding: 1rem;
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.alert-success {
  background-color: #D4EDDA;
  color: #155724;
  border: 1px solid #C3E6CB;
}

.alert-warning {
  background-color: #FFF3CD;
  color: #856404;
  border: 1px solid #FFE69C;
}

.alert-danger {
  background-color: #F8D7DA;
  color: #721C24;
  border: 1px solid #F5C6CB;
}

.alert-info {
  background-color: #D1ECF1;
  color: #0C5460;
  border: 1px solid #BEE5EB;
}
```

### Таблицы:
```css
.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--color-gray-200);
}

.table th {
  background-color: var(--color-gray-100);
  font-weight: var(--font-weight-semibold);
  color: var(--color-gray-700);
}

.table tbody tr:hover {
  background-color: var(--color-gray-50);
}
```

### Бейджи:
```css
.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  border-radius: 9999px;
}

.badge-primary {
  background-color: var(--color-primary);
  color: var(--color-white);
}

.badge-success {
  background-color: var(--color-success);
  color: var(--color-white);
}

.badge-warning {
  background-color: var(--color-warning);
  color: var(--color-black);
}

.badge-danger {
  background-color: var(--color-danger);
  color: var(--color-white);
}
```

### Прогресс-бар:
```css
.progress {
  height: 0.5rem;
  background-color: var(--color-gray-200);
  border-radius: 9999px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background-color: var(--color-primary);
  transition: width 0.3s ease;
}

.progress-bar-success {
  background-color: var(--color-success);
}

.progress-bar-warning {
  background-color: var(--color-warning);
}

.progress-bar-danger {
  background-color: var(--color-danger);
}
```

### Тултипы:
```css
.tooltip {
  position: relative;
  display: inline-block;
}

.tooltip .tooltip-text {
  visibility: hidden;
  width: 120px;
  background-color: var(--color-gray-900);
  color: var(--color-white);
  text-align: center;
  border-radius: 0.375rem;
  padding: 0.5rem;
  position: absolute;
  z-index: 1;
  bottom: 125%;
  left: 50%;
  transform: translateX(-50%);
  opacity: 0;
  transition: opacity 0.3s;
}

.tooltip:hover .tooltip-text {
  visibility: visible;
  opacity: 1;
}
```

---

## 🎮 Компоненты офиса

### Canvas контейнер:
```css
.office-canvas {
  width: 800px;
  height: 600px;
  background-color: var(--color-gray-100);
  border: 2px solid var(--color-gray-300);
  border-radius: 0.5rem;
  position: relative;
  overflow: hidden;
}
```

### Зона офиса:
```css
.office-zone {
  position: absolute;
  border: 2px dashed var(--color-gray-400);
  border-radius: 0.25rem;
  background-color: rgba(255, 255, 255, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--font-size-sm);
  color: var(--color-gray-600);
}

.office-zone-workspace {
  background-color: var(--zone-workspace);
  border-color: #90CAF9;
}

.office-zone-meeting-room {
  background-color: var(--zone-meeting-room);
  border-color: #FFCC80;
}

.office-zone-brainstorm {
  background-color: var(--zone-brainstorm);
  border-color: #CE93D8;
}

.office-zone-break-room {
  background-color: var(--zone-break-room);
  border-color: #A5D6A7;
}

.office-zone-cafeteria {
  background-color: var(--zone-cafeteria);
  border-color: #FFE082;
}

.office-zone-lounge {
  background-color: var(--zone-lounge);
  border-color: #F48FB1;
}
```

### Агент (пиксельный аватар):
```css
.agent {
  position: absolute;
  width: 32px;
  height: 32px;
  cursor: pointer;
  transition: transform 0.2s ease;
  image-rendering: pixelated;
}

.agent:hover {
  transform: scale(1.1);
  z-index: 10;
}

.agent-avatar {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.agent-name {
  position: absolute;
  bottom: -20px;
  left: 50%;
  transform: translateX(-50%);
  font-size: var(--font-size-xs);
  color: var(--color-gray-700);
  white-space: nowrap;
  background-color: rgba(255, 255, 255, 0.9);
  padding: 2px 4px;
  border-radius: 2px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.agent:hover .agent-name {
  opacity: 1;
}
```

### Миникарта:
```css
.minimap {
  position: absolute;
  bottom: 10px;
  right: 10px;
  width: 150px;
  height: 112px;
  background-color: var(--color-white);
  border: 2px solid var(--color-gray-300);
  border-radius: 0.25rem;
  overflow: hidden;
}

.minimap-canvas {
  width: 100%;
  height: 100%;
}

.minimap-viewport {
  position: absolute;
  border: 2px solid var(--color-primary);
  background-color: rgba(52, 152, 219, 0.1);
}
```

### Тепловая карта:
```css
.heatmap {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  opacity: 0.5;
}
```

---

## 🎨 Иконки

### Набор иконок:
```css
.icon {
  display: inline-block;
  width: 1em;
  height: 1em;
  vertical-align: middle;
  fill: currentColor;
}

.icon-xs { width: 0.75em; height: 0.75em; }
.icon-sm { width: 1em; height: 1em; }
.icon-md { width: 1.5em; height: 1.5em; }
.icon-lg { width: 2em; height: 2em; }
.icon-xl { width: 3em; height: 3em; }
```

### Иконки категорий:
```css
.icon-academic::before { content: "📚"; }
.icon-design::before { content: "🎨"; }
.icon-engineering::before { content: "⚙️"; }
.icon-game-development::before { content: "🎮"; }
.icon-marketing::before { content: "📢"; }
.icon-paid-media::before { content: "💰"; }
.icon-product::before { content: "📦"; }
.icon-project-management::before { content: "📋"; }
.icon-sales::before { content: "💼"; }
.icon-spatial-computing::before { content: "🖥️"; }
.icon-specialized::before { content: "🔧"; }
.icon-support::before { content: "🛟"; }
```

### Иконки зон:
```css
.icon-workspace::before { content: "💼"; }
.icon-meeting-room::before { content: "🤝"; }
.icon-brainstorm::before { content: "💡"; }
.icon-break-room::before { content: "🛋️"; }
.icon-cafeteria::before { content: "🍽️"; }
.icon-lounge::before { content: "☕"; }
```

### Иконки статусов:
```css
.icon-pending::before { content: "⏳"; }
.icon-in-progress::before { content: "🔄"; }
.icon-testing::before { content: "🧪"; }
.icon-completed::before { content: "✅"; }
.icon-failed::before { content: "❌"; }
```

### Иконки приоритетов:
```css
.icon-low::before { content: "🟢"; }
.icon-medium::before { content: "🟡"; }
.icon-high::before { content: "🟠"; }
.icon-critical::before { content: "🔴"; }
```

---

## 🎨 Аватары агентов

### Стиль пиксельных аватаров:
```css
.agent-avatar-pixel {
  width: 32px;
  height: 32px;
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
}
```

### Цвета аватаров по категориям:
```css
.agent-avatar-academic { background-color: var(--category-academic); }
.agent-avatar-design { background-color: var(--category-design); }
.agent-avatar-engineering { background-color: var(--category-engineering); }
.agent-avatar-game-development { background-color: var(--category-game-development); }
.agent-avatar-marketing { background-color: var(--category-marketing); }
.agent-avatar-paid-media { background-color: var(--category-paid-media); }
.agent-avatar-product { background-color: var(--category-product); }
.agent-avatar-project-management { background-color: var(--category-project-management); }
.agent-avatar-sales { background-color: var(--category-sales); }
.agent-avatar-spatial-computing { background-color: var(--category-spatial-computing); }
.agent-avatar-specialized { background-color: var(--category-specialized); }
.agent-avatar-support { background-color: var(--category-support); }
```

### Примеры аватаров:
```
Агент: Frontend Developer
Категория: Engineering
Цвет: #2ECC71 (зелёный)
Эмодзи: 🎨
Размер: 32x32 пикселя
Стиль: Pixel art

Агент: UI Designer
Категория: Design
Цвет: #9B59B6 (фиолетовый)
Эмодзи: 🎯
Размер: 32x32 пикселя
Стиль: Pixel art
```

---

## 📊 Анимации

### Переходы:
```css
.transition-fast {
  transition: all 0.15s ease;
}

.transition-normal {
  transition: all 0.3s ease;
}

.transition-slow {
  transition: all 0.5s ease;
}
```

### Анимации агентов:
```css
@keyframes agent-idle {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-2px); }
}

@keyframes agent-move {
  0% { transform: translate(0, 0); }
  100% { transform: translate(var(--move-x), var(--move-y)); }
}

@keyframes agent-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.agent-idle {
  animation: agent-idle 2s ease-in-out infinite;
}

.agent-moving {
  animation: agent-move 0.5s ease-out forwards;
}

.agent-typing {
  animation: agent-pulse 1s ease-in-out infinite;
}
```

### Анимации уведомлений:
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

@keyframes notification-slide-out {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}

.notification {
  animation: notification-slide-in 0.3s ease-out;
}

.notification-exit {
  animation: notification-slide-out 0.3s ease-out forwards;
}
```

---

## 📊 Тени

### Уровни теней:
```css
:root {
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);
}
```

### Применение теней:
```css
.shadow-sm { box-shadow: var(--shadow-sm); }
.shadow-md { box-shadow: var(--shadow-md); }
.shadow-lg { box-shadow: var(--shadow-lg); }
.shadow-xl { box-shadow: var(--shadow-xl); }
```

---

## 📊 Отступы

### Система отступов:
```css
:root {
  --spacing-0: 0;
  --spacing-1: 0.25rem;   /* 4px */
  --spacing-2: 0.5rem;    /* 8px */
  --spacing-3: 0.75rem;   /* 12px */
  --spacing-4: 1rem;      /* 16px */
  --spacing-5: 1.25rem;   /* 20px */
  --spacing-6: 1.5rem;    /* 24px */
  --spacing-8: 2rem;      /* 32px */
  --spacing-10: 2.5rem;   /* 40px */
  --spacing-12: 3rem;     /* 48px */
  --spacing-16: 4rem;     /* 64px */
  --spacing-20: 5rem;     /* 80px */
  --spacing-24: 6rem;     /* 96px */
}
```

---

## 📊 Радиусы скругления

### Радиусы:
```css
:root {
  --radius-none: 0;
  --radius-sm: 0.125rem;  /* 2px */
  --radius-md: 0.375rem;  /* 6px */
  --radius-lg: 0.5rem;    /* 8px */
  --radius-xl: 0.75rem;   /* 12px */
  --radius-2xl: 1rem;     /* 16px */
  --radius-full: 9999px;
}
```

---

## 📊 Z-index

### Слои:
```css
:root {
  --z-index-dropdown: 1000;
  --z-index-sticky: 1020;
  --z-index-fixed: 1030;
  --z-index-modal-backdrop: 1040;
  --z-index-modal: 1050;
  --z-index-popover: 1060;
  --z-index-tooltip: 1070;
  --z-index-toast: 1080;
}
```

---

## 📊 Breakpoints

### Адаптивные breakpoints:
```css
:root {
  --breakpoint-xs: 0;
  --breakpoint-sm: 576px;
  --breakpoint-md: 768px;
  --breakpoint-lg: 992px;
  --breakpoint-xl: 1200px;
  --breakpoint-2xl: 1400px;
}
```

---

## 📚 Дополнительные ресурсы

- [API спецификация](PHASE2_API_SPECIFICATION.md)
- [Отчёт об аудите](PHASE1_AUDIT_REPORT.md)
- [Техническое задание](PHASE1_TECHNICAL_SPECIFICATION.md)
- [Архитектурная диаграмма](PHASE1_ARCHITECTURE_DIAGRAM.md)

---

**Создано**: 2026-03-31  
**Агент**: @ui-designer  
**Статус**: ✅ Завершено
