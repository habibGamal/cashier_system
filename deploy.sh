#!/bin/sh
set -e
cd /var/www/turbo_restaurant
git reset --hard
git pull
cd /var/www/turbo_restaurant/larament
composer install
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
php artisan optimize:clear
php artisan optimize
