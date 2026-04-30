#!/usr/bin/env sh
set -e

php artisan config:cache
php artisan route:cache
php artisan migrate --force
