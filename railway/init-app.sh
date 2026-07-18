#!/bin/sh
set -eu

php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan storage:link --force
php artisan config:cache
php artisan event:cache
php artisan view:cache
