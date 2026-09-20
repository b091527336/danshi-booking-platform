#!/usr/bin/env sh
set -eu

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

if [ -n "${DBP_ADMIN_EMAIL:-}" ] && [ -n "${DBP_ADMIN_PASSWORD:-}" ]; then
    php artisan db:seed --force
fi

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
