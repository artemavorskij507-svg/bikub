# 🏗️ Фаза 1: Архитектурная диаграмма

**Агент**: @software-architect  
**Дата**: 2026-03-31  
**Статус**: ✅ Завершено

---

## 📊 Диаграмма компонентов

```mermaid
graph TB
    subgraph "Frontend"
        UI[UI Layer]
        Canvas[HTML5 Canvas]
        WS_Client[WebSocket Client]
    end

    subgraph "Backend"
        API[API Gateway]
        Auth[Auth Service]
        Agent[Agent Service]
        Office[Office Service]
        AI[AI Service]
        WS[WebSocket Service]
    end

    subgraph "Data"
        DB[(PostgreSQL)]
        Cache[(Redis)]
        Queue[Message Queue]
    end

    subgraph "External"
        OpenAI[OpenAI API]
        MCP[MCP Servers]
    end

    UI --> API
    Canvas --> WS_Client
    WS_Client --> WS
    API --> Auth
    API --> Agent
    API --> Office
    API --> AI
    Agent --> DB
    Office --> DB
    AI --> OpenAI
    AI --> MCP
    Auth --> Cache
    WS --> Queue
    Queue --> Agent
```

---

## 📊 Диаграмма последовательности

```mermaid
sequenceDiagram
    participant User
    participant UI
    participant API
    participant Agent
    participant AI
    participant DB

    User->>UI: Клик на агента
    UI->>API: GET /api/agents/{id}
    API->>Agent: Получить данные агента
    Agent->>DB: SELECT * FROM agents
    DB-->>Agent: Данные агента
    Agent-->>API: Агент найден
    API-->>UI: JSON response
    UI-->>User: Показать информацию

    User->>UI: Отправить сообщение
    UI->>API: POST /api/agents/{id}/chat
    API->>AI: Обработать сообщение
    AI->>OpenAI: GPT-4 запрос
    OpenAI-->>AI: Ответ
    AI-->>API: Ответ агента
    API-->>UI: JSON response
    UI-->>User: Показать ответ
```

---

## 📊 Диаграмма базы данных

```mermaid
erDiagram
    USERS ||--o{ AGENTS : owns
    AGENTS ||--o{ TASKS : assigned
    AGENTS ||--o{ MESSAGES : sends
    AGENTS }|--|| CATEGORIES : belongs
    AGENTS }|--|| OFFICE_ZONES : located
    TASKS ||--o{ TASK_LOGS : has
    OFFICE_ZONES ||--o{ AGENTS : contains

    USERS {
        int id PK
        string name
        string email
        string password
        string role
    }

    AGENTS {
        int id PK
        string name
        string slug
        int category_id FK
        int zone_id FK
        int x_position
        int y_position
        string avatar
        json config
    }

    CATEGORIES {
        int id PK
        string name
        string color
        string icon
    }

    OFFICE_ZONES {
        int id PK
        string name
        string icon
        string color
        int x_min
        int x_max
        int y_min
        int y_max
    }

    TASKS {
        int id PK
        string title
        string description
        int agent_id FK
        string status
        string priority
        datetime created_at
        datetime completed_at
    }

    TASK_LOGS {
        int id PK
        int task_id FK
        string action
        json data
        datetime created_at
    }

    MESSAGES {
        int id PK
        int agent_id FK
        int user_id FK
        string content
        string role
        datetime created_at
    }
```

---

## 📊 Диаграмма развертывания

```mermaid
graph LR
    subgraph "Production"
        LB[Load Balancer]
        Web1[Web Server 1]
        Web2[Web Server 2]
        API1[API Server 1]
        API2[API Server 2]
        WS1[WebSocket Server 1]
        WS2[WebSocket Server 2]
        DB[(PostgreSQL Primary)]
        DB_Replica[(PostgreSQL Replica)]
        Redis[(Redis Cluster)]
        Prometheus[Prometheus]
        Grafana[Grafana]
    end

    subgraph "External"
        CDN[CDN]
        OpenAI[OpenAI API]
    end

    CDN --> LB
    LB --> Web1
    LB --> Web2
    Web1 --> API1
    Web2 --> API2
    API1 --> DB
    API2 --> DB
    API1 --> Redis
    API2 --> Redis
    API1 --> OpenAI
    API2 --> OpenAI
    WS1 --> Redis
    WS2 --> Redis
    DB --> DB_Replica
    Prometheus --> API1
    Prometheus --> API2
    Prometheus --> WS1
    Prometheus --> WS2
    Grafana --> Prometheus
```

---

## 📊 Диаграмма потока данных

```mermaid
flowchart TD
    Start([Пользователь заходит]) --> Auth{Аутентификация}
    Auth -->|Успех| LoadOffice[Загрузка офиса]
    Auth -->|Ошибка| Login[Страница входа]
    Login --> Auth

    LoadOffice --> RenderCanvas[Рендеринг Canvas]
    RenderCanvas --> ConnectWS[Подключение WebSocket]
    ConnectWS --> ListenEvents[Прослушивание событий]

    ListenEvents --> AgentMove[Движение агентов]
    ListenEvents --> NewMessage[Новые сообщения]
    ListenEvents --> TaskUpdate[Обновление задач]

    AgentMove --> UpdateCanvas[Обновление Canvas]
    NewMessage --> ShowNotification[Показать уведомление]
    TaskUpdate --> UpdateUI[Обновить UI]

    UpdateCanvas --> End([Готово])
    ShowNotification --> End
    UpdateUI --> End
```

---

## 📊 Диаграмма классов

```mermaid
classDiagram
    class User {
        +int id
        +string name
        +string email
        +string password
        +string role
        +login()
        +logout()
    }

    class Agent {
        +int id
        +string name
        +string slug
        +int category_id
        +int zone_id
        +int x_position
        +int y_position
        +string avatar
        +json config
        +move(x, y)
        +chat(message)
        +executeTask(task)
    }

    class Category {
        +int id
        +string name
        +string color
        +string icon
        +getAgents()
    }

    class OfficeZone {
        +int id
        +string name
        +string icon
        +string color
        +int x_min
        +int x_max
        +int y_min
        +int y_max
        +getAgents()
        +isInside(x, y)
    }

    class Task {
        +int id
        +string title
        +string description
        +int agent_id
        +string status
        +string priority
        +datetime created_at
        +datetime completed_at
        +assign(agent)
        +complete()
        +log(action)
    }

    class Message {
        +int id
        +int agent_id
        +int user_id
        +string content
        +string role
        +datetime created_at
        +send()
    }

    User "1" --> "*" Agent : owns
    Agent "*" --> "1" Category : belongs
    Agent "*" --> "1" OfficeZone : located
    Agent "1" --> "*" Task : assigned
    Agent "1" --> "*" Message : sends
    Task "1" --> "*" TaskLog : has
```

---

## 📊 Диаграмма состояний агента

```mermaid
stateDiagram-v2
    [*] --> Idle
    Idle --> Moving : move()
    Moving --> Idle : arrived()
    Idle --> Chatting : receiveMessage()
    Chatting --> Processing : processMessage()
    Processing --> Responding : generateResponse()
    Responding --> Idle : sendResponse()
    Idle --> Working : assignTask()
    Working --> Completed : finishTask()
    Completed --> Idle : reset()
    Idle --> Error : exception()
    Error --> Idle : recover()
```

---

## 📊 Диаграмма офиса

```mermaid
graph TB
    subgraph "Офис 800x600"
        subgraph "Рабочая зона (0-600, 0-400)"
            W1[Стол 1]
            W2[Стол 2]
            W3[Стол 3]
            W4[Стол 4]
        end

        subgraph "Переговорная (620-800, 0-200)"
            M1[Стол переговоров]
            M2[Проектор]
        end

        subgraph "Зона мозгового штурма (620-800, 220-400)"
            B1[Доска]
            B2[Стикеры]
        end

        subgraph "Зона отдыха (0-300, 420-580)"
            R1[Диван]
            R2[Растения]
        end

        subgraph "Столовая (320-600, 420-580)"
            C1[Столы]
            C2[Автоматы]
        end

        subgraph "Лаунж (620-800, 420-580)"
            L1[Кофе-машина]
            L2[Кресла]
        end
    end
```

---

## 📊 Диаграмма API

```mermaid
graph LR
    subgraph "API Endpoints"
        GET_agents[GET /api/agents]
        GET_agent[GET /api/agents/{id}]
        POST_agent[POST /api/agents]
        PUT_agent[PUT /api/agents/{id}]
        DELETE_agent[DELETE /api/agents/{id}]

        GET_tasks[GET /api/tasks]
        POST_task[POST /api/tasks]
        PUT_task[PUT /api/tasks/{id}]

        GET_messages[GET /api/messages]
        POST_message[POST /api/messages]

        GET_office[GET /api/office]
        PUT_office[PUT /api/office]

        WS_agents[WS /ws/agents]
        WS_office[WS /ws/office]
    end
```

---

## 📊 Диаграмма безопасности

```mermaid
graph TB
    subgraph "Security Layers"
        WAF[Web Application Firewall]
        LB[Load Balancer]
        SSL[SSL/TLS Termination]
        Auth[Authentication]
        AuthZ[Authorization]
        RateLimit[Rate Limiting]
        CORS[CORS Policy]
        CSRF[CSRF Protection]
        InputVal[Input Validation]
        OutputEnc[Output Encoding]
    end

    WAF --> LB
    LB --> SSL
    SSL --> Auth
    Auth --> AuthZ
    AuthZ --> RateLimit
    RateLimit --> CORS
    CORS --> CSRF
    CSRF --> InputVal
    InputVal --> OutputEnc
```

---

## 📚 Дополнительные ресурсы

- [Отчёт об аудите](PHASE1_AUDIT_REPORT.md)
- [Техническое задание](PHASE1_TECHNICAL_SPECIFICATION.md)
- [План разработки](VIRTUAL_2D_OFFICE_DEVELOPMENT_PLAN.md)

---

**Создано**: 2026-03-31  
**Агент**: @software-architect  
**Статус**: ✅ Завершено
