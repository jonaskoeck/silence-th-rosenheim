#!/bin/sh
set -e

# Im Image gebaute Vite-Assets in das gemountete public/build spiegeln, damit
# der Apache-Container (serviert public/ aus dem Host-Mount) sie ausliefert.
# Ohne diesen Schritt laedt das Browser-Bundle nicht -> kein htmx, kein Bootstrap.
if [ -d /build-dist ]; then
    mkdir -p /web/public/build
    cp -a /build-dist/. /web/public/build/
fi

php artisan config:cache
php artisan route:cache
php artisan migrate --force

exec "$@"
