#!/bin/sh
set -e

# Caches are built at start-up because configuration comes from the
# environment file, which is never baked into the image.
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

exec "$@"
