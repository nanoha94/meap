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
echo "📖 詳細ドキュメント: tests/docs/04_Services/02_BillingWebhookService_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: BillingWebhookServiceTest.php"
./vendor/bin/sail test tests/Feature/Services/BillingWebhookServiceTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/04_Services/03_BillingService_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: BillingServiceTest.php"
./vendor/bin/sail test tests/Feature/Services/BillingServiceTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/04_Services/04_AiRecipeService_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: AiRecipeServiceTest.php"
./vendor/bin/sail test tests/Unit/Services/Ai/AiRecipeServiceTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "✅ Services関連テスト完了"
echo "=========================================="
