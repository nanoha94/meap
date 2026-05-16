#!/bin/sh
set -e
PORT="${PORT:-8080}"
export PORT
sed "s/__PORT__/${PORT}/g" /etc/nginx/templates/default.conf > /etc/nginx/conf.d/default.conf
cd /var/www/html
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
