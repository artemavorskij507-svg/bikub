# Docker PoC for BiKuBe — Ollama + Postgres(pgvector) + Redis + Laravel PHP/NGINX

## Что делает этот PoC
- Запускает локальный Ollama контейнер (порт 11434) — runtime для локальных LLM (Mistral и прочие).  
- Запускает Postgres с включённым расширением `pgvector` (скрипт в `docker/initdb/`) для RAG/векторного поиска.  
- Redis для очередей Laravel.  
- Простая конфигурация PHP-FPM + nginx для локальной разработки (порт сайта `http://localhost:2244`).

## Быстрый старт
1. Установи Docker и Docker Compose на машине.
2. В корне репозитория запусти:
   ```bash
   docker compose up -d
   ```
3. Подтяни модель в Ollama (скрипт сделает `ollama pull mistral:7b-instruct` внутри контейнера):
   ```bash
   chmod +x scripts/ollama-pull.sh
   ./scripts/ollama-pull.sh
   ```
   (Имя модели `mistral:7b-instruct` — используется библиотека Ollama; при необходимости уточни актуальный тэг на странице Ollama Models).

4. После успешного `pull` Ollama будет доступен по адресу `http://localhost:11434` и ваш Laravel код (AIModerationService) может дергать `OLLAMA_URL=http://ollama:11434`.

## Проверка Ollama API
Пример запроса (локально, с машины):
```bash
curl -s -X POST "http://localhost:11434/api/generate" \
  -H "Content-Type: application/json" \
  -d '{"model":"mistral:7b-instruct","prompt":"Привет, напиши короткий текст"}'
```

Если ответ есть — интеграция работает.

## Примечания и рекомендации
- Для GPU-ускорения запусти контейнер Ollama с флагом `--gpus=all` (и установи NVIDIA Container Toolkit), см. Ollama docs.  
- В продакшне лучше переносить inference на выделенные GPU-ноды и рассматривать vLLM/Triton для производительности.

