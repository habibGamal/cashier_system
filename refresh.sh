php artisan config:clear
php artisan cache:clear
php artisan config:cache
rc-service php-fpm83 restart
rc-service nginx restart
npm run build
php artisan queue:restart  # if applicable

