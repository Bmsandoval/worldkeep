# Build context: prototypes/worldkeep (repo root for this PoC)

FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
        libzip-dev libsqlite3-dev libpq-dev zip unzip curl \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql pcntl bcmath \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV WORLDKEEP_CAMPAIGN_ID=campaign_001 \
    WORLDKEEP_SRD_VERSION=srd-2014 \
    WORLDKEEP_ROLE=owner

WORKDIR /var/www/html

COPY web/composer.json web/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY web/ .

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/views storage/framework/cache/data \
        storage/framework/sessions storage/logs bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

RUN printf '%s\n' \
        '<VirtualHost *:80>' \
        '    DocumentRoot /var/www/html/public' \
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

ARG APP_PORT=80
EXPOSE ${APP_PORT}

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
