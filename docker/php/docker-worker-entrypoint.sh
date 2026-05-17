#!/bin/bash
set -euo pipefail

READY_FILE="/var/www/html/bootstrap/cache/app-ready"

echo "Waiting for php container bootstrap (${READY_FILE})..."
while [ ! -f "$READY_FILE" ]; do
    sleep 2
done

exec "$@"
