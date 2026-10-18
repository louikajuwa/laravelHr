#!/bin/sh
set -e

cd /var/www/html

# Runs as the unprivileged `app` user. Ownership of storage/, bootstrap/cache
# and vendor/ is fixed beforehand by the `app-init` service (see compose).
if [ -d storage ]; then
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
fi

exec "$@"
