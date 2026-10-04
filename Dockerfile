# syntax=docker/dockerfile:1
FROM dunglas/frankenphp:1-php8.4

WORKDIR /app

RUN install-php-extensions apcu intl opcache pdo_pgsql zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    SERVER_NAME=":8080"

# Install dependencies first so code changes don't bust the vendor layer
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

COPY . .
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint \
    && composer dump-autoload --no-dev --classmap-authoritative \
    && composer run-script --no-dev post-install-cmd \
    && bin/console asset-map:compile \
    && chown -R www-data:www-data var /data/caddy /config/caddy

USER www-data
EXPOSE 8080
ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
