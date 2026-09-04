#!/bin/sh
set -e
PORT="${PORT:-8080}"
export PORT
echo "[meap] docker-entrypoint: PORT=${PORT}"
rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default 2>/dev/null || true
sed "s/__PORT__/${PORT}/g" /etc/nginx/templates/default.conf > /etc/nginx/conf.d/default.conf
cd /var/www/html
if [ ! -f public/index.php ]; then
    echo "[meap] ERROR: public/index.php not found (check Railway Root Directory=server)" >&2
    exit 1
fi

# ランタイム書き込み先の権限を整える（Railway 等で root 起動→非 root 降格するため）
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/cache/nginx \
    /var/log/nginx \
    /var/lib/nginx \
    /etc/nginx/conf.d

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
echo "[meap] starting supervisord (nginx + php-fpm) as www-data"
exec gosu www-data /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
