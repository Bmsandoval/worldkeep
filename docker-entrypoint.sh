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

# Sidecar packaging: bind Apache to APP_PORT so each prototype can co-locate in
# one Fargate task on a distinct port. Defaults to 80 (standalone / local dev).
APP_PORT="${APP_PORT:-80}"
sed -ri "s/^Listen[[:space:]]+[0-9]+/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -ri "s|<VirtualHost \*:[0-9]+>|<VirtualHost *:${APP_PORT}>|" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
