# Build context: prototypes/worldkeep (repo root for this PoC)

# Go binaries
FROM golang:1.23-bookworm AS go-builder
WORKDIR /src/mcp
COPY mcp/go.mod mcp/go.sum ./
RUN go mod download
COPY mcp/ ./
RUN CGO_ENABLED=0 go build -o /out/worldkeep-serve ./cmd/worldkeep-serve \
    && CGO_ENABLED=0 go build -o /out/worldkeep-seed ./cmd/seed

# PHP + Apache (Laravel UI + reverse proxy to Go)
FROM php:8.4-apache

RUN apt-get update && apt-get install -y libzip-dev libsqlite3-dev zip unzip curl \
    && docker-php-ext-install pdo pdo_sqlite pcntl bcmath \
    && a2enmod rewrite proxy proxy_http headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=go-builder /out/worldkeep-serve /usr/local/bin/worldkeep-serve
COPY --from=go-builder /out/worldkeep-seed /usr/local/bin/worldkeep-seed

ENV WORLDKEEP_DATA_DIR=/var/worldkeep/data \
    WORLDKEEP_HTTP_ADDR=127.0.0.1:8788 \
    WORLDKEEP_CAMPAIGN_ID=campaign_001 \
    WORLDKEEP_INTERNAL_URL=http://127.0.0.1:8788

WORKDIR /var/www/html

COPY web/composer.json web/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY web/ .

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/views storage/framework/cache/data \
        storage/framework/sessions storage/logs bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

RUN mkdir -p /var/worldkeep/data \
    && printf '%s\n' \
        '<VirtualHost *:80>' \
        '    DocumentRoot /var/www/html/public' \
        '    ProxyPreserveHost On' \
        '    ProxyPass /mcp http://127.0.0.1:8788/mcp' \
        '    ProxyPassReverse /mcp http://127.0.0.1:8788/mcp' \
        '    ProxyPass /api/v1 http://127.0.0.1:8788/api/v1' \
        '    ProxyPassReverse /api/v1 http://127.0.0.1:8788/api/v1' \
        '    ProxyPass /healthz http://127.0.0.1:8788/healthz' \
        '    ProxyPassReverse /healthz http://127.0.0.1:8788/healthz' \
        '    <Directory /var/www/html/public>' \
        '        AllowOverride All' \
        '        Require all granted' \
        '    </Directory>' \
        '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
        '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
        '</VirtualHost>' \
        > /etc/apache2/sites-available/000-default.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
