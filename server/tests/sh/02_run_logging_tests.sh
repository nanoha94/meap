#!/bin/bash
# ログ関連テスト実行スクリプト
echo "=========================================="
echo "📝 ログ関連テスト実行"
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/02_TRAIT_TEST_SPECIFICATIONS.md"
echo ""

# LoggingTraitテスト実行
echo "実行中: LoggingTraitTest.php"
./vendor/bin/sail test tests/Feature/Logging/LoggingTraitTest.php --stop-on-failure

echo ""

# ExceptionHandlerTraitテスト実行
echo "実行中: ExceptionHandlerTraitTest.php"
./vendor/bin/sail test tests/Feature/Logging/ExceptionHandlerTraitTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📋 ログ出力確認:"
echo "=========================================="

# ログファイルの存在確認
if [ -f "storage/logs/laravel.log" ]; then
  echo "📋 最新のログエントリ (最後の10行):"
  tail -10 storage/logs/laravel.log
  echo ""
  echo "📋 ログレベル別の確認:"
  echo "  ERROR ログ: $(grep -c 'ERROR' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo "  INFO ログ: $(grep -c 'INFO' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo "  WARNING ログ: $(grep -c 'WARNING' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo ""
  echo "💡 リアルタイムログ確認コマンド:"
  echo "  tail -f storage/logs/laravel.log"
else
  echo "⚠️  ログファイルが見つかりません: storage/logs/laravel.log"
fi

echo ""
echo "=========================================="
echo "✅ ログ関連テスト完了"
echo "=========================================="
