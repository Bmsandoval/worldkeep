#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ -z "${APP_KEY:-}" ]; then
  php artisan key:generate --force --no-interaction
fi

mkdir -p database storage/framework/views storage/framework/cache/data \
  storage/framework/sessions storage/logs bootstrap/cache

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  touch database/database.sqlite
fi

chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

php artisan config:cache --no-interaction
php artisan migrate --force --no-interaction
php artisan worldkeep:seed --no-interaction

chown -R www-data:www-data storage bootstrap/cache database

exec apache2-foreground
