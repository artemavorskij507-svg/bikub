# Bikube Smart Assistant

AI-ассистент для курьеров с интеграцией OpenAI GPT-4o-mini.

## Установка и настройка

### 1. Настройка переменных окружения

Добавьте в `.env`:

```env
# AI provider
OPENAI_API_KEY=your_openai_api_key_here
AI_PROVIDER=openai
AI_MODEL=gpt-4o-mini
BIKUBE_ASSISTANT_DEFAULT_PROMPT="You are Bikube Smart Assistant. Help couriers with concise, actionable instructions. Use local Narvik context."
```

### 2. Выполнение миграций

```bash
php artisan migrate
```

### 3. Инициализация контекста

Загрузите локальный контекст из файла:

```bash
php artisan assistant:ingest-context /mnt/data/Доставка.txt
```

Это создаст системное сообщение с контекстом проекта для ассистента.

### 4. Запуск очереди

Ассистент использует очереди для генерации ответов. Запустите worker:

```bash
php artisan queue:work
```

Или настройте Supervisor для автоматического запуска.

## API Endpoints

### Создание разговора

```http
POST /api/v1/assistant/conversations
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Courier chat"
}
```

### Отправка сообщения

```http
POST /api/v1/assistant/conversations/{conversation_id}/messages
Authorization: Bearer {token}
Content-Type: application/json

{
  "message": "Где ближайший магазин?"
}
```

### Получение сообщений

```http
GET /api/v1/assistant/conversations/{conversation_id}/messages
Authorization: Bearer {token}
```

## Планировщик задач

Ассистент автоматически отправляет подсказки курьерам каждые 5 минут:

```php
$schedule->command('assistant:broadcast')->everyFiveMinutes();
```

## WebSocket Broadcasting

Сообщения транслируются через Laravel Broadcasting на канал:

```
private-assistant.conversation.{conversation_id}
```

## Filament Admin

В админ-панели доступен ресурс для просмотра разговоров:

- `/admin/assistant-conversations` - список всех разговоров

## Структура базы данных

### assistant_conversations
- `id` - ID разговора
- `subject_type` / `subject_id` - полиморфная связь (Order, User и т.д.)
- `title` - название разговора
- `channel` - канал (courier, admin, order)
- `created_by` - ID создателя

### assistant_messages
- `id` - ID сообщения
- `assistant_conversation_id` - ID разговора
- `user_id` - ID пользователя (если сообщение от пользователя)
- `role` - роль (user, assistant, system)
- `content` - текст сообщения
- `meta` - дополнительные данные (JSON)
- `from_ai` - флаг, что сообщение от AI

## Использование

1. Курьер создает разговор через API
2. Отправляет сообщение
3. Сообщение сохраняется в БД
4. Запускается Job `GenerateAssistantReply`
5. AI генерирует ответ на основе истории разговора и системного промпта
6. Ответ транслируется через WebSocket
7. Курьер видит ответ в реальном времени

## Примечания

- Контекст проекта загружается из файла `/mnt/data/Доставка.txt`
- Разговоры сохраняются в базе данных для истории
- AI использует GPT-4o-mini для баланса скорости и качества
- Максимальная длина ответа: 1024 токена

