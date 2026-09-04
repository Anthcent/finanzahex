#!/bin/bash
set -e

# Ensure required writable storage subdirectories exist with proper permissions
mkdir -p /var/www/html/writable/cache \
         /var/www/html/writable/logs \
         /var/www/html/writable/session \
         /var/www/html/writable/uploads \
         /var/www/html/writable/debugbar
chown -R www-data:www-data /var/www/html/writable || true
chmod -R 775 /var/www/html/writable || true

# If database connection is provided (e.g. Dokploy / Hexper Ops DATABASE_URL or DB_HOST), run migrations idempotently
if [ -n "$DATABASE_URL" ] || [ -n "$DB_HOST" ]; then
    echo "=> [Dokploy/Hexper Ops] Executing pending database migrations..."
    php spark migrate --all || {
        echo "=> [Notice] Migrations reported status: $?. Continuing startup..."
    }
fi

# Execute container main command (apache2-foreground)
exec "$@"
