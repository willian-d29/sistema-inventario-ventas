#!/bin/sh
set -e

echo "Esperando a MySQL..."
until nc -z db 3306; do
  sleep 1
done

cd /var/www

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
