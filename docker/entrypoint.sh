#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

if [ ! -L public/storage ]; then
    php artisan storage:link --force >/dev/null 2>&1 || true
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Waiting for MySQL at ${DB_HOST:-host.docker.internal}:${DB_PORT:-3306}..."
    i=0
    until php -r '
        $host = getenv("DB_HOST") ?: "host.docker.internal";
        $port = getenv("DB_PORT") ?: "3306";
        $db   = getenv("DB_DATABASE") ?: "phonehub";
        $user = getenv("DB_USERNAME") ?: "root";
        $pass = getenv("DB_PASSWORD") ?: "";
        new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass);
    ' >/dev/null 2>&1; do
        i=$((i + 1))
        if [ "$i" -ge 30 ]; then
            echo "MySQL is unreachable. Check bind-address, user host, and DB_* in .env"
            exit 1
        fi
        sleep 2
    done

    php artisan migrate --force
fi

exec "$@"
