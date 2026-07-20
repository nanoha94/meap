#!/bin/bash
# 全テスト実行スクリプト
echo "=========================================="
echo "🚀 MEAP アプリケーション 全テスト実行"
echo "=========================================="
echo "📖 認証テスト仕様: tests/docs/01_AUTHENTICATION_TEST_SPECIFICATIONS.md"
echo "📖 ログテスト仕様: tests/docs/02_TRAIT_TEST_SPECIFICATIONS.md"
echo ""

# Traitsテスト実行
echo "=========================================="
echo "[1] Traits関連テスト実行"
echo "=========================================="
./tests/sh/01_run_traits_tests.sh

echo ""

# 認証テスト実行
echo "=========================================="
echo "[2] 認証関連テスト実行"
echo "=========================================="
./tests/sh/02_run_auth_tests.sh

echo ""

# APIテスト実行
echo "=========================================="
echo "[3] APIテスト実行"
echo "=========================================="
./tests/sh/03_run_api_tests.sh

echo ""

# Servicesテスト実行
echo "=========================================="
echo "[4] Servicesテスト実行"
echo "=========================================="
./tests/sh/04_run_services_tests.sh

echo ""

# Helpersテスト実行
echo "=========================================="
echo "[5] Helpersテスト実行"
echo "=========================================="
./tests/sh/05_run_helpers_tests.sh

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
