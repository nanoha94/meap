#!/bin/bash
# Helpers関連テスト実行スクリプト
echo "=========================================="
echo "🚀 Helpers関連テスト実行"
echo "=========================================="
echo ""

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/05_Helpers/01_Quantity_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: QuantityTest.php"
./vendor/bin/sail test tests/Unit/Helpers/QuantityTest.php --stop-on-failure

echo ""
echo "📖 詳細ドキュメント: tests/docs/05_Helpers/02_SafeUrlFetcher_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: SafeUrlFetcherTest.php"
./vendor/bin/sail test tests/Unit/Helpers/SafeUrlFetcherTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "✅ Helpers関連テスト完了"
echo "=========================================="
