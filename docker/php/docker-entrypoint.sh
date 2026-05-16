#!/bin/bash
set -euo pipefail

LOCK_FILE="/var/www/html/bootstrap/cache/entrypoint.lock"
mkdir -p /var/www/html/bootstrap/cache

(
    flock -x 99

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
) 99>"$LOCK_FILE"

exec "$@"
