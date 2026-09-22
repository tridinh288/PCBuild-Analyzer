# Production image for the Laravel API (Render web service, D-024).
# Build context: backend/. Local development uses docker/php/Dockerfile.dev instead.

# --- Stage 1: PHP dependencies without dev packages -------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# --- Stage 2: runtime ---------------------------------------------------------------------------
FROM php:8.3-cli-alpine AS runtime

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql bcmath intl opcache \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html

# Package discovery was skipped with --no-scripts in stage 1; build the manifest now.
# Non-root user; Laravel only writes to storage/ and bootstrap/cache/.
RUN php artisan package:discover --ansi \
    && addgroup -S -g 10001 laravel \
    && adduser -S -D -H -u 10001 -G laravel -h /var/www/html laravel \
    && chown -R laravel:laravel storage bootstrap/cache \
    && chmod +x docker/start.sh
USER laravel

# Render sets $PORT; 8000 is the local default.
ENV PORT=8000
EXPOSE 8000

CMD ["docker/start.sh"]
