#!/bin/sh
set -e
PORT="${PORT:-8080}"
export PORT
sed "s/__PORT__/${PORT}/g" /etc/nginx/templates/default.conf > /etc/nginx/conf.d/default.conf
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
