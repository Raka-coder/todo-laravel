#!/bin/sh
set -e

# Cache configuration & routes jika APP_ENV=production
if [ "$APP_ENV" = "production" ]; then
    echo "Running in production mode..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Jalankan database migrations secara aman (tanpa drop)
echo "Running database migrations..."
php artisan migrate --force --no-interaction || echo "Migration skipped or failed"

# Jalankan CMD asli (php-fpm)
exec "$@"
