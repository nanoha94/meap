#!/bin/sh
set -e
PORT="${PORT:-8080}"
export PORT
echo "[meap] docker-entrypoint: PORT=${PORT}"
sed "s/__PORT__/${PORT}/g" /etc/nginx/templates/default.conf > /etc/nginx/conf.d/default.conf
cd /var/www/html
if [ ! -f public/index.php ]; then
    echo "[meap] ERROR: public/index.php not found (check Railway Root Directory=server)" >&2
    exit 1
fi
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
echo "[meap] starting supervisord (nginx + php-fpm)"
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
