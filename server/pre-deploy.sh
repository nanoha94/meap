#!/bin/bash

echo "Migration Start..."
php artisan migrate:reset
php artisan migrate
echo "Migration End"