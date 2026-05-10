# 🔌 Фаза 2: API спецификация (OpenAPI/Swagger)

**Агент**: @backend-architect  
**Дата**: 2026-03-31  
**Статус**: ✅ Завершено

---

## 📋 Обзор

API спецификация для 2D виртуального офиса с пиксельными агентами. Включает все endpoints для управления агентами, задачами, сообщениями и офисом.

**Base URL**: `https://api.bikube.no/v2`  
**Version**: 2.0.0  
**Format**: OpenAPI 3.0.0

---

## 🔐 Аутентификация

### Тип: Bearer Token (JWT)
```yaml
securitySchemes:
  bearerAuth:
    type: http
    scheme: bearer
    bearerFormat: JWT
```

### Получение токена:
```yaml
POST /auth/login:
  summary: Аутентификация пользователя
  requestBody:
    required: true
    content:
      application/json:
        schema:
          type: object
          properties:
            email:
              type: string
              format: email
              example: "keks@glf.no"
            password:
              type: string
              format: password
              example: "6636"
  responses:
    200:
      description: Успешная аутентификация
      content:
        application/json:
          schema:
            type: object
            properties:
              access_token:
                type: string
                example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
              token_type:
                type: string
                example: "bearer"
              expires_in:
                type: integer
                example: 3600
              user:
                $ref: '#/components/schemas/User'
```

---

## 📊 Схемы данных

### User
```yaml
components:
  schemas:
    User:
      type: object
      properties:
        id:
          type: integer
          example: 1
        name:
          type: string
          example: "Keks"
        email:
          type: string
          format: email
          example: "keks@glf.no"
        role:
          type: string
          enum: [admin, user, viewer]
          example: "admin"
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
```

### Agent
```yaml
    Agent:
      type: object
      properties:
        id:
          type: integer
          example: 1
        name:
          type: string
          example: "Frontend Developer"
        slug:
          type: string
          example: "frontend-developer"
        description:
          type: string
          example: "Expert frontend developer specializing in React/Vue/Angular"
        category_id:
          type: integer
          example: 3
        zone_id:
          type: integer
          example: 1
        x_position:
          type: integer
          example: 350
        y_position:
          type: integer
          example: 100
        avatar:
          type: string
          nullable: true
          example: "/storage/avatars/frontend-developer.png"
        emoji:
          type: string
          example: "🎨"
        color:
          type: string
          example: "#2ECC71"
        is_active:
          type: boolean
          example: true
        category:
          $ref: '#/components/schemas/Category'
        zone:
          $ref: '#/components/schemas/OfficeZone'
        configs:
          type: array
          items:
            $ref: '#/components/schemas/AgentConfig'
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
```

### Category
```yaml
    Category:
      type: object
      properties:
        id:
          type: integer
          example: 3
        name:
          type: string
          example: "Engineering"
        slug:
          type: string
          example: "engineering"
        description:
          type: string
          example: "Software development and architecture"
        color:
          type: string
          example: "#2ECC71"
        icon:
          type: string
          example: "⚙️"
        sector_x_min:
          type: integer
          example: 320
        sector_x_max:
          type: integer
          example: 470
        sector_y_min:
          type: integer
          example: 0
        sector_y_max:
          type: integer
          example: 200
        agents_count:
          type: integer
          example: 26
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
```

### OfficeZone
```yaml
    OfficeZone:
      type: object
      properties:
        id:
          type: integer
          example: 1
        name:
          type: string
          example: "Рабочая зона"
        slug:
          type: string
          example: "workspace"
        icon:
          type: string
          example: "💼"
        color:
          type: string
          example: "#e3f2fd"
        x_min:
          type: integer
          example: 0
        x_max:
          type: integer
          example: 600
        y_min:
          type: integer
          example: 0
        y_max:
          type: integer
          example: 400
        capacity:
          type: integer
          example: 50
        amenities:
          type: array
          items:
            type: string
          example: ["desks", "monitors", "chairs", "power_outlets"]
        agents_count:
          type: integer
          example: 25
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
```

### Task
```yaml
    Task:
      type: object
      properties:
        id:
          type: integer
          example: 1
        title:
          type: string
          example: "Создать API для управления агентами"
        description:
          type: string
          example: "Реализовать REST API endpoints для CRUD операций"
        agent_id:
          type: integer
          example: 5
        status:
          type: string
          enum: [pending, in_progress, testing, completed, failed]
          example: "in_progress"
        priority:
          type: string
          enum: [low, medium, high, critical]
          example: "high"
        deadline:
          type: string
          format: date-time
          nullable: true
          example: "2026-04-01T18:00:00Z"
        agent:
          $ref: '#/components/schemas/Agent'
        logs:
          type: array
          items:
            $ref: '#/components/schemas/TaskLog'
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
        completed_at:
          type: string
          format: date-time
          nullable: true
```

### TaskLog
```yaml
    TaskLog:
      type: object
      properties:
        id:
          type: integer
          example: 1
        task_id:
          type: integer
          example: 1
        action:
          type: string
          example: "status_changed"
        data:
          type: object
          example: {"from": "pending", "to": "in_progress"}
        created_at:
          type: string
          format: date-time
```

### Message
```yaml
    Message:
      type: object
      properties:
        id:
          type: integer
          example: 1
        agent_id:
          type: integer
          example: 5
        user_id:
          type: integer
          example: 1
        content:
          type: string
          example: "Привет! Как дела?"
        role:
          type: string
          enum: [user, agent]
          example: "user"
        agent:
          $ref: '#/components/schemas/Agent'
        user:
          $ref: '#/components/schemas/User'
        created_at:
          type: string
          format: date-time
```

### AgentConfig
```yaml
    AgentConfig:
      type: object
      properties:
        id:
          type: integer
          example: 1
        agent_id:
          type: integer
          example: 5
        config_key:
          type: string
          example: "personality"
        config_value:
          type: object
          example: {"traits": ["detail-oriented", "performance-focused"]}
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
```

---

## 🔌 API Endpoints

### Аутентификация

#### POST /auth/login
```yaml
/auth/login:
  post:
    tags:
      - Auth
    summary: Аутентификация пользователя
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            required:
              - email
              - password
            properties:
              email:
                type: string
                format: email
              password:
                type: string
                format: password
    responses:
      200:
        description: Успешная аутентификация
      401:
        description: Неверные учетные данные
```

#### POST /auth/logout
```yaml
/auth/logout:
  post:
    tags:
      - Auth
    summary: Выход из системы
    security:
      - bearerAuth: []
    responses:
      200:
        description: Успешный выход
```

#### POST /auth/refresh
```yaml
/auth/refresh:
  post:
    tags:
      - Auth
    summary: Обновление токена
    security:
      - bearerAuth: []
    responses:
      200:
        description: Токен обновлен
```

---

### Агенты

#### GET /agents
```yaml
/agents:
  get:
    tags:
      - Agents
    summary: Получить список всех агентов
    security:
      - bearerAuth: []
    parameters:
      - name: category_id
        in: query
        schema:
          type: integer
        description: Фильтр по категории
      - name: zone_id
        in: query
        schema:
          type: integer
        description: Фильтр по зоне
      - name: is_active
        in: query
        schema:
          type: boolean
        description: Фильтр по активности
      - name: search
        in: query
        schema:
          type: string
        description: Поиск по имени или описанию
      - name: page
        in: query
        schema:
          type: integer
          default: 1
        description: Номер страницы
      - name: per_page
        in: query
        schema:
          type: integer
          default: 50
        description: Количество на странице
    responses:
      200:
        description: Список агентов
        content:
          application/json:
            schema:
              type: object
              properties:
                data:
                  type: array
                  items:
                    $ref: '#/components/schemas/Agent'
                meta:
                  type: object
                  properties:
                    current_page:
                      type: integer
                    last_page:
                      type: integer
                    per_page:
                      type: integer
                    total:
                      type: integer
```

#### POST /agents
```yaml
/agents:
  post:
    tags:
      - Agents
    summary: Создать нового агента
    security:
      - bearerAuth: []
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            required:
              - name
              - slug
              - category_id
            properties:
              name:
                type: string
              slug:
                type: string
              description:
                type: string
              category_id:
                type: integer
              zone_id:
                type: integer
              x_position:
                type: integer
              y_position:
                type: integer
              avatar:
                type: string
              emoji:
                type: string
              color:
                type: string
              is_active:
                type: boolean
                default: true
    responses:
      201:
        description: Агент создан
      422:
        description: Ошибка валидации
```

#### GET /agents/{id}
```yaml
/agents/{id}:
  get:
    tags:
      - Agents
    summary: Получить агента по ID
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      200:
        description: Данные агента
      404:
        description: Агент не найден
```

#### PUT /agents/{id}
```yaml
/agents/{id}:
  put:
    tags:
      - Agents
    summary: Обновить агента
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            properties:
              name:
                type: string
              description:
                type: string
              category_id:
                type: integer
              zone_id:
                type: integer
              x_position:
                type: integer
              y_position:
                type: integer
              avatar:
                type: string
              emoji:
                type: string
              color:
                type: string
              is_active:
                type: boolean
    responses:
      200:
        description: Агент обновлен
      404:
        description: Агент не найден
      422:
        description: Ошибка валидации
```

#### DELETE /agents/{id}
```yaml
/agents/{id}:
  delete:
    tags:
      - Agents
    summary: Удалить агента
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      204:
        description: Агент удален
      404:
        description: Агент не найден
```

#### POST /agents/{id}/move
```yaml
/agents/{id}/move:
  post:
    tags:
      - Agents
    summary: Переместить агента
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            required:
              - x
              - y
            properties:
              x:
                type: integer
                minimum: 0
                maximum: 800
              y:
                type: integer
                minimum: 0
                maximum: 600
    responses:
      200:
        description: Агент перемещен
      404:
        description: Агент не найден
      422:
        description: Неверные координаты
```

#### POST /agents/{id}/chat
```yaml
/agents/{id}/chat:
  post:
    tags:
      - Agents
    summary: Отправить сообщение агенту
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            required:
              - message
            properties:
              message:
                type: string
                maxLength: 5000
    responses:
      200:
        description: Ответ агента
        content:
          application/json:
            schema:
              type: object
              properties:
                message:
                  $ref: '#/components/schemas/Message'
                response:
                  $ref: '#/components/schemas/Message'
      404:
        description: Агент не найден
```

---

### Категории

#### GET /categories
```yaml
/categories:
  get:
    tags:
      - Categories
    summary: Получить все категории
    security:
      - bearerAuth: []
    responses:
      200:
        description: Список категорий
        content:
          application/json:
            schema:
              type: array
              items:
                $ref: '#/components/schemas/Category'
```

#### GET /categories/{id}
```yaml
/categories/{id}:
  get:
    tags:
      - Categories
    summary: Получить категорию по ID
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      200:
        description: Данные категории
      404:
        description: Категория не найдена
```

#### GET /categories/{id}/agents
```yaml
/categories/{id}/agents:
  get:
    tags:
      - Categories
    summary: Получить агентов категории
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      200:
        description: Список агентов категории
        content:
          application/json:
            schema:
              type: array
              items:
                $ref: '#/components/schemas/Agent'
      404:
        description: Категория не найдена
```

---

### Офисные зоны

#### GET /office-zones
```yaml
/office-zones:
  get:
    tags:
      - Office Zones
    summary: Получить все офисные зоны
    security:
      - bearerAuth: []
    responses:
      200:
        description: Список зон
        content:
          application/json:
            schema:
              type: array
              items:
                $ref: '#/components/schemas/OfficeZone'
```

#### GET /office-zones/{id}
```yaml
/office-zones/{id}:
  get:
    tags:
      - Office Zones
    summary: Получить зону по ID
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      200:
        description: Данные зоны
      404:
        description: Зона не найдена
```

#### GET /office-zones/{id}/agents
```yaml
/office-zones/{id}/agents:
  get:
    tags:
      - Office Zones
    summary: Получить агентов в зоне
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      200:
        description: Список агентов в зоне
        content:
          application/json:
            schema:
              type: array
              items:
                $ref: '#/components/schemas/Agent'
      404:
        description: Зона не найдена
```

---

### Задачи

#### GET /tasks
```yaml
/tasks:
  get:
    tags:
      - Tasks
    summary: Получить все задачи
    security:
      - bearerAuth: []
    parameters:
      - name: status
        in: query
        schema:
          type: string
          enum: [pending, in_progress, testing, completed, failed]
        description: Фильтр по статусу
      - name: agent_id
        in: query
        schema:
          type: integer
        description: Фильтр по агенту
      - name: priority
        in: query
        schema:
          type: string
          enum: [low, medium, high, critical]
        description: Фильтр по приоритету
      - name: page
        in: query
        schema:
          type: integer
          default: 1
      - name: per_page
        in: query
        schema:
          type: integer
          default: 50
    responses:
      200:
        description: Список задач
        content:
          application/json:
            schema:
              type: object
              properties:
                data:
                  type: array
                  items:
                    $ref: '#/components/schemas/Task'
                meta:
                  type: object
                  properties:
                    current_page:
                      type: integer
                    last_page:
                      type: integer
                    per_page:
                      type: integer
                    total:
                      type: integer
```

#### POST /tasks
```yaml
/tasks:
  post:
    tags:
      - Tasks
    summary: Создать новую задачу
    security:
      - bearerAuth: []
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            required:
              - title
              - agent_id
            properties:
              title:
                type: string
              description:
                type: string
              agent_id:
                type: integer
              priority:
                type: string
                enum: [low, medium, high, critical]
                default: medium
              deadline:
                type: string
                format: date-time
                nullable: true
    responses:
      201:
        description: Задача создана
      422:
        description: Ошибка валидации
```

#### GET /tasks/{id}
```yaml
/tasks/{id}:
  get:
    tags:
      - Tasks
    summary: Получить задачу по ID
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      200:
        description: Данные задачи
      404:
        description: Задача не найдена
```

#### PUT /tasks/{id}
```yaml
/tasks/{id}:
  put:
    tags:
      - Tasks
    summary: Обновить задачу
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            properties:
              title:
                type: string
              description:
                type: string
              agent_id:
                type: integer
              status:
                type: string
                enum: [pending, in_progress, testing, completed, failed]
              priority:
                type: string
                enum: [low, medium, high, critical]
              deadline:
                type: string
                format: date-time
                nullable: true
    responses:
      200:
        description: Задача обновлена
      404:
        description: Задача не найдена
      422:
        description: Ошибка валидации
```

#### DELETE /tasks/{id}
```yaml
/tasks/{id}:
  delete:
    tags:
      - Tasks
    summary: Удалить задачу
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      204:
        description: Задача удалена
      404:
        description: Задача не найдена
```

---

### Сообщения

#### GET /messages
```yaml
/messages:
  get:
    tags:
      - Messages
    summary: Получить все сообщения
    security:
      - bearerAuth: []
    parameters:
      - name: agent_id
        in: query
        schema:
          type: integer
        description: Фильтр по агенту
      - name: user_id
        in: query
        schema:
          type: integer
        description: Фильтр по пользователю
      - name: page
        in: query
        schema:
          type: integer
          default: 1
      - name: per_page
        in: query
        schema:
          type: integer
          default: 50
    responses:
      200:
        description: Список сообщений
        content:
          application/json:
            schema:
              type: object
              properties:
                data:
                  type: array
                  items:
                    $ref: '#/components/schemas/Message'
                meta:
                  type: object
                  properties:
                    current_page:
                      type: integer
                    last_page:
                      type: integer
                    per_page:
                      type: integer
                    total:
                      type: integer
```

#### GET /messages/{id}
```yaml
/messages/{id}:
  get:
    tags:
      - Messages
    summary: Получить сообщение по ID
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      200:
        description: Данные сообщения
      404:
        description: Сообщение не найдено
```

#### DELETE /messages/{id}
```yaml
/messages/{id}:
  delete:
    tags:
      - Messages
    summary: Удалить сообщение
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: integer
    responses:
      204:
        description: Сообщение удалено
      404:
        description: Сообщение не найдено
```

---

### Офис

#### GET /office
```yaml
/office:
  get:
    tags:
      - Office
    summary: Получить состояние офиса
    security:
      - bearerAuth: []
    responses:
      200:
        description: Состояние офиса
        content:
          application/json:
            schema:
              type: object
              properties:
                dimensions:
                  type: object
                  properties:
                    width:
                      type: integer
                      example: 800
                    height:
                      type: integer
                      example: 600
                zones:
                  type: array
                  items:
                    $ref: '#/components/schemas/OfficeZone'
                agents:
                  type: array
                  items:
                    $ref: '#/components/schemas/Agent'
                stats:
                  type: object
                  properties:
                    total_agents:
                      type: integer
                    active_agents:
                      type: integer
                    total_tasks:
                      type: integer
                    pending_tasks:
                      type: integer
```

#### PUT /office/settings
```yaml
/office/settings:
  put:
    tags:
      - Office
    summary: Обновить настройки офиса
    security:
      - bearerAuth: []
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            properties:
              width:
                type: integer
                minimum: 400
                maximum: 1920
              height:
                type: integer
                minimum: 300
                maximum: 1080
              update_interval:
                type: integer
                minimum: 1000
                maximum: 10000
              movement_speed:
                type: number
                minimum: 0.5
                maximum: 5
              enable_animations:
                type: boolean
              enable_heatmap:
                type: boolean
              enable_minimap:
                type: boolean
    responses:
      200:
        description: Настройки обновлены
      422:
        description: Ошибка валидации
```

---

## 📊 WebSocket Events

### Подключение
```yaml
ws://localhost:6001/ws
```

### События

#### agent:moved
```json
{
  "event": "agent:moved",
  "data": {
    "agent_id": 5,
    "x": 350,
    "y": 100,
    "timestamp": "2026-03-31T20:30:00Z"
  }
}
```

#### agent:status_changed
```json
{
  "event": "agent:status_changed",
  "data": {
    "agent_id": 5,
    "status": "active",
    "timestamp": "2026-03-31T20:30:00Z"
  }
}
```

#### task:created
```json
{
  "event": "task:created",
  "data": {
    "task_id": 1,
    "title": "Создать API",
    "agent_id": 5,
    "timestamp": "2026-03-31T20:30:00Z"
  }
}
```

#### task:updated
```json
{
  "event": "task:updated",
  "data": {
    "task_id": 1,
    "status": "in_progress",
    "timestamp": "2026-03-31T20:30:00Z"
  }
}
```

#### message:sent
```json
{
  "event": "message:sent",
  "data": {
    "message_id": 1,
    "agent_id": 5,
    "user_id": 1,
    "content": "Привет!",
    "timestamp": "2026-03-31T20:30:00Z"
  }
}
```

#### message:received
```json
{
  "event": "message:received",
  "data": {
    "message_id": 2,
    "agent_id": 5,
    "user_id": 1,
    "content": "Привет! Как дела?",
    "timestamp": "2026-03-31T20:30:00Z"
  }
}
```

---

## 📊 Error Responses

### 400 Bad Request
```json
{
  "error": {
    "code": 400,
    "message": "Bad Request",
    "details": "Invalid JSON format"
  }
}
```

### 401 Unauthorized
```json
{
  "error": {
    "code": 401,
    "message": "Unauthorized",
    "details": "Invalid or expired token"
  }
}
```

### 403 Forbidden
```json
{
  "error": {
    "code": 403,
    "message": "Forbidden",
    "details": "Insufficient permissions"
  }
}
```

### 404 Not Found
```json
{
  "error": {
    "code": 404,
    "message": "Not Found",
    "details": "Resource not found"
  }
}
```

### 422 Unprocessable Entity
```json
{
  "error": {
    "code": 422,
    "message": "Unprocessable Entity",
    "details": {
      "name": ["The name field is required."],
      "email": ["The email must be a valid email address."]
    }
  }
}
```

### 429 Too Many Requests
```json
{
  "error": {
    "code": 429,
    "message": "Too Many Requests",
    "details": "Rate limit exceeded. Try again in 60 seconds."
  }
}
```

### 500 Internal Server Error
```json
{
  "error": {
    "code": 500,
    "message": "Internal Server Error",
    "details": "An unexpected error occurred"
  }
}
```

---

## 📊 Rate Limiting

### Лимиты:
- **Authenticated users**: 100 requests/minute
- **Unauthenticated users**: 10 requests/minute
- **WebSocket connections**: 10 connections/user

### Headers:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1648000000
```

---

## 📊 Pagination

### Параметры:
- `page`: Номер страницы (default: 1)
- `per_page`: Количество на странице (default: 50, max: 100)

### Response format:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 50,
    "total": 500
  }
}
```

---

## 📊 Filtering

### Параметры фильтрации:
- `category_id`: Фильтр по категории
- `zone_id`: Фильтр по зоне
- `is_active`: Фильтр по активности
- `status`: Фильтр по статусу
- `priority`: Фильтр по приоритету
- `search`: Поиск по имени или описанию

### Пример:
```
GET /api/v2/agents?category_id=3&is_active=true&search=frontend
```

---

## 📊 Sorting

### Параметры сортировки:
- `sort`: Поле для сортировки (name, created_at, updated_at)
- `order`: Порядок сортировки (asc, desc)

### Пример:
```
GET /api/v2/agents?sort=name&order=asc
```

---

## 📚 Дополнительные ресурсы

- [Отчёт об аудите](PHASE1_AUDIT_REPORT.md)
- [Техническое задание](PHASE1_TECHNICAL_SPECIFICATION.md)
- [Архитектурная диаграмма](PHASE1_ARCHITECTURE_DIAGRAM.md)
- [User stories](PHASE1_USER_STORIES.md)
- [План миграции данных](PHASE1_DATA_MIGRATION_PLAN.md)

---

**Создано**: 2026-03-31  
**Агент**: @backend-architect  
**Статус**: ✅ Завершено
