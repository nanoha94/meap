#!/bin/bash

echo "Pre Deploy Start..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan tinker --execute="dump(config('database.connections.mysql'))"
echo "Migration Start..."
php artisan migrate:reset
php artisan migrate
echo "Migration End"
echo "Pre Deploy End"