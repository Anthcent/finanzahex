#!/bin/bash
set -e

# Ensure permissions on writable storage for Apache www-data user
chown -R www-data:www-data /var/www/html/writable || true
chmod -R 775 /var/www/html/writable || true

# If database connection is provided (e.g. Dokploy / Hexper Ops DATABASE_URL), run migrations idempotently
if [ -n "$DATABASE_URL" ] || [ -n "$DB_HOST" ] || [ -n "$database.default.hostname" ]; then
    echo "=> [Dokploy/Hexper Ops] Executing pending database migrations..."
    php spark migrate --all || {
        echo "=> [Notice] Migrations completed or reported status: $?. Continuing startup..."
    }
fi

# Execute container main command (apache2-foreground)
exec "$@"
