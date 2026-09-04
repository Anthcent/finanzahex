#!/bin/sh
set -eu

cd /var/www/html
mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar

# Do not accept traffic against an unavailable or incomplete schema.
php spark app:prepare
exec "$@"
