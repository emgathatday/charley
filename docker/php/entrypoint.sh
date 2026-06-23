#!/usr/bin/env bash
set -e

cd /var/www/html

mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

if [ ! -f .env ] && [ -f .env.docker.example ]; then
    cp .env.docker.example .env
fi

if [ ! -d vendor ] && [ -f composer.json ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f artisan ] && ! grep -Eq '^APP_KEY=.+$' .env; then
    php artisan key:generate --ansi --force --no-interaction >/dev/null 2>&1 || true
fi

exec "$@"
