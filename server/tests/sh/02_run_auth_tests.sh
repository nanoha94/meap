#!/bin/bash
# 認証関連テスト実行スクリプト
echo "=========================================="
echo "🔐 認証関連テスト実行"
echo "=========================================="
echo "📖 詳細ドキュメント: tests/docs/01_AUTHENTICATION_TEST_SPECIFICATIONS.md"
echo ""

# テスト実行
echo "実行中: Auth関連テストファイル群"
echo "  - AuthenticatedSessionControllerTest.php (セッション認証)"
echo "  - PasswordResetLinkControllerTest.php (パスワードリセット)"
echo "  - RegisteredUserControllerTest.php (ユーザー登録)"
echo "  - VerifyEmailControllerTest.php (メール認証)"
echo ""

./vendor/bin/sail test tests/Feature/Auth/ --stop-on-failure

echo ""
echo "=========================================="
echo "✅ 認証関連テスト完了"
echo "=========================================="
