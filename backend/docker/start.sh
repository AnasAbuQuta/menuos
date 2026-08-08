#!/bin/sh
set -eu

if [ "$#" -gt 1 ]; then
    exec "$@"
fi

if [ "$#" -eq 1 ]; then
    exec "$1"
fi

PORT="${PORT:-10000}"

case "$PORT" in
    ''|*[!0-9]*)
        echo "PORT must be a numeric value." >&2
        exit 1
        ;;
esac

if [ "${APP_ENV:-production}" = "production" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo "APP_KEY is required in production." >&2
        exit 1
    fi

    if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
        echo "SQLite is not supported for the production Render deployment. Set DB_CONNECTION=pgsql." >&2
        exit 1
    fi
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

php artisan migrate --force

if [ "${SEED_DEMO_RESTAURANT:-false}" = "true" ]; then
    php artisan db:seed --force
fi

if [ -n "${MENUOS_BOOTSTRAP_SUPER_ADMIN_EMAIL:-}" ]; then
    php artisan menuos:make-super-admin "$MENUOS_BOOTSTRAP_SUPER_ADMIN_EMAIL" --force
fi

if [ ! -L public/storage ]; then
    if [ -e public/storage ]; then
        echo "public/storage exists but is not a symbolic link; refusing to overwrite it." >&2
        exit 1
    fi

    php artisan storage:link
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

envsubst '${PORT}' \
    < /etc/nginx/templates/menuos.conf.template \
    > /etc/nginx/conf.d/menuos.conf

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/menuos.conf
