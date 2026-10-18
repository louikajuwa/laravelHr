#!/bin/sh
set -e

cd /var/www/html

# Ensure Laravel writable dirs are writable by php-fpm workers (dev convenience).
if [ -d storage ]; then
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
    chmod -R 777 storage bootstrap/cache 2>/dev/null || true
fi

exec "$@"
