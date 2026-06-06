#!/bin/bash
# Services関連テスト実行スクリプト
echo "=========================================="
echo "🚀 Services関連テスト実行"
echo "=========================================="
echo ""

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/04_Services/01_AiUsageService_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: AiUsageServiceTest.php"
./vendor/bin/sail test tests/Feature/Services/AiUsageServiceTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "✅ Services関連テスト完了"
echo "=========================================="
