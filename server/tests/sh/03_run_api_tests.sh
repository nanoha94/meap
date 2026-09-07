#!/bin/bash
# API関連テスト実行スクリプト
echo "=========================================="
echo "🚀 API関連テスト実行"
echo "=========================================="
echo ""

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/01_ImageController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: ImageControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/ImageControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/03_InvitationController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: InvitationControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/InvitationControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/04_MealCategoryController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: MealCategoryControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/MealCategoryControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/05_MealPlanController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: MealPlanControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/MealPlanControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/06_RecipeCategoryController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: RecipeCategoryControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/RecipeCategoryControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/07_RecipeController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: RecipeControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/RecipeControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/08_ShoppingCategoryController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: ShoppingCategoryControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/ShoppingCategoryControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/09_ShoppingItemController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: ShoppingItemControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/ShoppingItemControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/10_ShoppingTagController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: ShoppingTagControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/ShoppingTagControllerTest.php --stop-on-failure

# UserControllerテスト実行
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/11_UserController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: UserControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/UserControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/12_IngredientUnitController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: IngredientUnitControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/IngredientUnitControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/13_AiRecipeController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: AiRecipeControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/AiRecipeControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/14_AiUsageController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: AiUsageControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/AiUsageControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/15_BillingController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: BillingControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/BillingControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/03_Api/16_MasterController_TEST_SPECIFICATIONS.md"
echo ""
echo "実行中: MasterControllerTest.php"
./vendor/bin/sail test tests/Feature/Api/MasterControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📋 APIテスト結果確認:"
echo "=========================================="

# API関連のログファイルの存在確認
if [ -f "storage/logs/laravel.log" ]; then
  echo "📋 最新のAPI関連ログエントリ (最後の10行):"
  tail -10 storage/logs/laravel.log
  echo ""
  echo "📋 API関連ログレベル別の確認:"
  echo "  ERROR ログ: $(grep -c 'ERROR' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo "  INFO ログ: $(grep -c 'INFO' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo "  WARNING ログ: $(grep -c 'WARNING' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo ""
  echo "💡 リアルタイムAPIログ確認コマンド:"
  echo "  tail -f storage/logs/laravel.log"
else
  echo "⚠️  ログファイルが見つかりません: storage/logs/laravel.log"
fi

echo ""
echo "=========================================="
echo "✅ API関連テスト完了"
echo "=========================================="

