#!/bin/bash

echo "=== ログテストの実行 ==="
echo ""

# ログディレクトリの確認
echo "1. ログディレクトリの確認..."
if [ -d "storage/logs" ]; then
    echo "✓ storage/logs ディレクトリが存在します"
else
    echo "✗ storage/logs ディレクトリが存在しません"
    mkdir -p storage/logs
    echo "✓ storage/logs ディレクトリを作成しました"
fi

# ログファイルの確認
echo ""
echo "2. ログファイルの確認..."
if [ -f "storage/logs/laravel.log" ]; then
    echo "✓ laravel.log ファイルが存在します"
    echo "現在のログサイズ: $(du -h storage/logs/laravel.log | cut -f1)"
else
    echo "✗ laravel.log ファイルが存在しません"
    touch storage/logs/laravel.log
    echo "✓ laravel.log ファイルを作成しました"
fi

# テストの実行
echo ""
echo "3. ログテストの実行..."
echo "テストを実行中..."

# Pestを使用してテストを実行
if [ -f "./vendor/bin/sail" ]; then
    ./vendor/bin/sail artisan test --filter="LoggingTraitTest|ExceptionHandlerTraitTest|LoggingIntegrationTest"
else
    echo "✗ ./vendor/bin/sail が見つかりません"
    echo "Laravelプロジェクトのルートディレクトリで実行してください"
    exit 1
fi

# ログファイルの内容確認
echo ""
echo "4. ログファイルの内容確認..."
if [ -f "storage/logs/laravel.log" ]; then
    echo "=== 最新のログエントリ ==="
    tail -20 storage/logs/laravel.log
    echo ""
    echo "=== ログファイルの統計 ==="
    echo "総行数: $(wc -l < storage/logs/laravel.log)"
    echo "ファイルサイズ: $(du -h storage/logs/laravel.log | cut -f1)"
else
    echo "✗ ログファイルが見つかりません"
fi

echo ""
echo "=== ログテスト完了 ==="
echo ""
echo "ログファイルの詳細確認:"
echo "tail -f storage/logs/laravel.log"
echo ""
echo "特定のログレベルでフィルタリング:"
echo "grep 'ERROR' storage/logs/laravel.log"
echo "grep 'INFO' storage/logs/laravel.log"
echo "grep 'WARNING' storage/logs/laravel.log"
