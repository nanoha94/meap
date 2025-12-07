#!/bin/bash
# Auth関連テスト実行スクリプト
echo "=========================================="
echo "🔐 Auth関連テスト実行"
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/Auth/01_AuthenticatedSessionController_TEST_SPECIFICATIONS.md"
echo ""

# AuthenticatedSessionControllerテスト実行
echo "実行中: AuthenticatedSessionControllerTest.php"
./vendor/bin/sail test tests/Feature/Auth/AuthenticatedSessionControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/Auth/02_EmailVerificationNotificationController_TEST_SPECIFICATIONS.md"
echo ""

# EmailVerificationNotificationControllerテスト実行
echo "実行中: EmailVerificationNotificationControllerTest.php"
./vendor/bin/sail test tests/Feature/Auth/EmailVerificationNotificationControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/Auth/03_NewPasswordController_TEST_SPECIFICATIONS.md"
echo ""

# NewPasswordControllerテスト実行
echo "実行中: NewPasswordControllerTest.php"
./vendor/bin/sail test tests/Feature/Auth/NewPasswordControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/Auth/04_PasswordResetLinkController_TEST_SPECIFICATIONS.md"
echo ""

# PasswordResetLinkControllerテスト実行
echo "実行中: PasswordResetLinkControllerTest.php"
./vendor/bin/sail test tests/Feature/Auth/PasswordResetLinkControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/Auth/05_RegisteredUserController_TEST_SPECIFICATIONS.md"
echo ""

# RegisteredUserControllerテスト実行
echo "実行中: RegisteredUserControllerTest.php"
./vendor/bin/sail test tests/Feature/Auth/RegisteredUserControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/Auth/06_VerifyEmailController_TEST_SPECIFICATIONS.md"
echo ""

# VerifyEmailControllerテスト実行
echo "実行中: VerifyEmailControllerTest.php"
./vendor/bin/sail test tests/Feature/Auth/VerifyEmailControllerTest.php --stop-on-failure

echo ""
echo "=========================================="
echo "📋 認証テスト結果確認:"
echo "=========================================="

# 認証関連のログファイルの存在確認
if [ -f "storage/logs/laravel.log" ]; then
  echo "📋 最新の認証関連ログエントリ (最後の10行):"
  tail -10 storage/logs/laravel.log
  echo ""
  echo "📋 認証関連ログレベル別の確認:"
  echo "  ERROR ログ: $(grep -c 'ERROR' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo "  INFO ログ: $(grep -c 'INFO' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo "  WARNING ログ: $(grep -c 'WARNING' storage/logs/laravel.log 2>/dev/null || echo 0) 件"
  echo ""
  echo "💡 リアルタイム認証ログ確認コマンド:"
  echo "  tail -f storage/logs/laravel.log"
else
  echo "⚠️  ログファイルが見つかりません: storage/logs/laravel.log"
fi

echo ""
echo "=========================================="
echo "✅ Auth関連テスト完了"
echo "=========================================="
