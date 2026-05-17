#!/bin/bash
set -euo pipefail

READY_FILE="/var/www/html/bootstrap/cache/app-ready"

rm -f "$READY_FILE"
mkdir -p /var/www/html/bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
    php artisan notifications:ensure-queues
fi

touch "$READY_FILE"

exec "$@"
