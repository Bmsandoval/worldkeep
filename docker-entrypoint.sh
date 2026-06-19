#!/bin/sh
set -e

DATA_DIR="${WORLDKEEP_DATA_DIR:-/var/worldkeep/data}"
CAMPAIGN="${WORLDKEEP_CAMPAIGN_ID:-campaign_001}"

mkdir -p "$DATA_DIR"

if [ ! -f "$DATA_DIR/${CAMPAIGN}.sqlite" ]; then
  echo "worldkeep: seeding demo campaign ${CAMPAIGN} ..."
  /usr/local/bin/worldkeep-seed
fi

export WORLDKEEP_HTTP_ADDR="${WORLDKEEP_HTTP_ADDR:-127.0.0.1:8788}"

echo "worldkeep: starting Go engine on ${WORLDKEEP_HTTP_ADDR} ..."
/usr/local/bin/worldkeep-serve &
GO_PID=$!

for _ in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20; do
  if curl -sf "http://127.0.0.1:8788/healthz" >/dev/null 2>&1; then
    break
  fi
  sleep 0.25
done

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --force --no-interaction
fi

mkdir -p database storage/framework/sessions storage/logs bootstrap/cache
touch database/database.sqlite
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

php artisan config:cache --no-interaction
php artisan migrate --force --no-interaction

chown -R www-data:www-data storage bootstrap/cache database

trap 'kill "$GO_PID" 2>/dev/null || true' EXIT INT TERM

exec apache2-foreground
