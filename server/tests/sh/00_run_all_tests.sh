#!/bin/bash
# 全テスト実行スクリプト
echo "=========================================="
echo "🚀 MEAP アプリケーション 全テスト実行"
echo "=========================================="
echo "📖 認証テスト仕様: tests/docs/01_AUTHENTICATION_TEST_SPECIFICATIONS.md"
echo "📖 ログテスト仕様: tests/docs/02_TRAIT_TEST_SPECIFICATIONS.md"
echo ""

# 認証テスト実行
echo "=========================================="
echo "[1] 認証関連テスト実行"
echo "=========================================="
./tests/sh/01_run_auth_tests.sh

echo ""

# ログテスト実行
echo "=========================================="
echo "[2] ログ関連テスト実行"
echo "=========================================="
./tests/sh/02_run_logging_tests.sh

echo ""

# 結果サマリー
echo "=========================================="
echo "🎉 全テスト実行完了"
echo "=========================================="
echo ""
echo "📖 ドキュメント参照:"
echo "- 認証テスト: tests/docs/01_AUTHENTICATION_TEST_SPECIFICATIONS.md"
echo "- ログテスト: tests/docs/02_TRAIT_TEST_SPECIFICATIONS.md"
echo ""
echo "💡 テストケースは番号付きタイトルで実行されます"
echo "   (例: 1-1: 正常ログイン、2-1: 機密情報フィルタリングテスト)"
