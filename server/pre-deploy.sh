#!/bin/bash

# マイグレーションのリセット
echo "Running migrate:reset..."
php artisan migrate:reset || { echo "migrate:reset failed"; exit 1; }

# マイグレーションの実行
echo "Running migrate..."
php artisan migrate || { echo "migrate failed"; exit 1; }

# 成功した場合にメッセージを表示
echo "Migration completed successfully."