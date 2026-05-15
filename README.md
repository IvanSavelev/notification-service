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
RabbitMQ Management: `http://localhost:15672` (guest/guest)

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

В Docker:

```bash
composer test:docker
```

Интеграционные тесты проверяют цепочку API → очередь (fake/sync) → БД → mock-провайдер.

## Моки шлюзов

| subscriber_id   | Поведение              |
|----------------|------------------------|
| `invalid-*`    | permanent → discarded  |
| `transient-*`  | transient → retry      |
| остальные      | успешная доставка      |
