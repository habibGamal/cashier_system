#!/bin/sh
set -e
cd /var/www/turbo_restaurant/larament
git reset --hard
git pull
if [ ! -x /usr/local/bin/wkhtmltoimage ]; then
    if [ -f ./wkhtmltoimage ]; then
        echo "Installing wkhtmltoimage to /usr/local/bin"
        cp ./wkhtmltoimage /usr/local/bin/wkhtmltoimage
        chmod +x /usr/local/bin/wkhtmltoimage
    else
        echo "Warning: ./wkhtmltoimage not found, skipping installation"
    fi
fi
if ! apk info -e font-dejavu > /dev/null 2>&1; then
  echo "🖋 Installing font-dejavu..."
  apk update && apk add font-dejavu
  echo "✅ font-dejavu installed successfully."
else
  echo "✔ font-dejavu is already installed."
fi
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
