#!/bin/sh
# Container start script (Render free tier has no pre-deploy command, spec section 33).
set -e

# Apply schema changes before serving traffic.
php artisan migrate --force

# Demo data on the first deploy only: set SEED_DATABASE=true once, then remove it.
# The seeders are idempotent, so an accidental re-run does not duplicate data.
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    php artisan db:seed --force
fi

# Cache configuration and routes: env() is read only here, never at request time.
php artisan config:cache
php artisan route:cache

# PHP's built-in server with several workers (Laravel reads PHP_CLI_SERVER_WORKERS).
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" --no-reload
