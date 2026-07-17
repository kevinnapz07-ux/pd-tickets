#!/bin/sh
set -eu

php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan view:cache
