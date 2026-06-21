#!/bin/sh
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi
if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
