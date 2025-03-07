#!/bin/bash

echo "Pre Deploy Start..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan tinker --execute="dump(config('database.connections.mysql'))"
echo "...Pre Deploy End"