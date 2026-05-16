# Notification Service

Микросервис массовых SMS/Email-уведомлений с приоритизацией, гарантией доставки (at-least-once), идемпотентностью и детализацией статусов.

## Стек

- PHP 8.3, Laravel 12
- PostgreSQL
- RabbitMQ (`vladimir-yuldashev/laravel-queue-rabbitmq`)
- Redis (кэш / дедупликация)
- Nginx + PHP-FPM (без `php artisan serve`)

## Запуск

```bash
cp .env.example .env
# при необходимости: php artisan key:generate
docker compose up --build
```

API: `http://localhost:8080`  
**Swagger (OpenAPI):** [http://localhost:8080/docs](http://localhost:8080/docs) — спецификация: `/docs/openapi.yaml`  
RabbitMQ Management: `http://localhost:15672` (guest/guest)

### Xdebug (разработка)

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build php nginx
```

1. В Cursor/VS Code: **Run and Debug** → `Listen for Xdebug (Docker)` (порт **9003**).
2. Поставьте breakpoint в PHP-коде.
3. Запустите запрос с триггером (режим `trigger` по умолчанию):
   - браузерное расширение Xdebug, или
   - `http://localhost:8080/?XDEBUG_TRIGGER=1`, или
   - заголовок `Cookie: XDEBUG_TRIGGER=1` в `tests.http`.

IDE слушает на хосте, PHP в контейнере подключается на `host.docker.internal:9003`.

Всегда включать отладку (медленнее): в `docker/php/conf.d/xdebug.ini` замените  
`xdebug.start_with_request=trigger` на `yes` и пересоберите образ.

Проверка: `docker compose exec php php -v` — в выводе должно быть `with Xdebug`.

## API

### Массовая рассылка

`POST /api/v1/notifications/bulk`

Заголовок: `Idempotency-Key: <uuid>`

```json
{
  "channel": "sms",
  "message": "Ваш код: 1234",
  "recipient_ids": ["user-1", "user-2"],
  "priority": "critical"
}
```

`priority`: `critical` (транзакционные, очередь `notifications-critical`) или `normal` (маркетинг, `notifications-normal`).

### История подписчика

`GET /api/v1/subscribers/{subscriberId}/notifications?status=delivered&per_page=50`

Статусы: `queued`, `sent`, `delivered`, `discarded`.

## Архитектура

1. API принимает bulk-запрос, проверяет идемпотентность (Redis + unique в БД), создаёт записи со статусом `queued`.
2. Задачи публикуются в RabbitMQ (разные очереди по приоритету).
3. Worker (`rabbitmq:consume`) обрабатывает `SendNotificationJob`: mock-шлюз → `sent` → `ConfirmDeliveryJob` → `delivered` / `discarded`.
4. Повторные попытки при временных сбоях шлюза (префикс `transient-` у subscriber_id в моках).
5. Exactly-once на уровне бизнес-логики: обработка только записей в статусе `queued`.

## Тесты

Локально (нужны расширения `pdo_sqlite`, PHP 8.4):

```bash
composer test
```

В Docker (unit/feature, без RabbitMQ):

```bash
composer test:docker
```

### Интеграционные тесты (RabbitMQ + Redis + PostgreSQL)

Нужен поднятый стек: `docker compose up -d`.

```bash
composer test:integration
```

Отдельный suite: `phpunit.integration.xml`, каталог `tests/Integration/`.  
Проверяется реальная цепочка: API → RabbitMQ → worker (`rabbitmq:consume --once`) → mock-шлюз → `delivered`.

Локально (без Docker для PHP), если сервисы на localhost:

```bash
DB_HOST=127.0.0.1 REDIS_HOST=127.0.0.1 RABBITMQ_HOST=127.0.0.1 \
  vendor/bin/phpunit -c phpunit.integration.xml
```

Feature-тесты (`tests/Feature`) используют `Queue::fake()` и sqlite — это быстрые тесты без брокера.

## Моки шлюзов

| subscriber_id   | Поведение              |
|----------------|------------------------|
| `invalid-*`    | permanent → discarded  |
| `transient-*`  | transient → retry      |
| остальные      | успешная доставка      |
