export const PASSWORD_RESET_STATUS_MESSAGES: Record<string, string> = {
    success: 'パスワードをリセットしました。',
};

export const OAUTH_ERROR_MESSAGES: Record<string, string> = {
    oauth_state_invalid:
        'セッションの有効期限が切れました。もう一度お試しください。',
    oauth_failed: 'Google認証に失敗しました。もう一度お試しください。',
    oauth_no_email:
        'Googleアカウントからメールアドレスを取得できませんでした。',
    oauth_email_unverified:
        '同じメールアドレスのアカウントが未認証のため、Google連携できません。メール認証後に再度お試しください。',
};

export const EMAIL_VERIFY_ERROR_MESSAGES: Record<string, string> = {
    unauthenticated:
        'ログインしてから、もう一度メール内のリンクをクリックしてください',
    invalid_link:
        '認証リンクが無効または期限切れです。認証メールを再送してください',
    verification_failed: 'メール認証に失敗しました。もう一度お試しください',
    database_error:
        'エラーが発生しました。時間をおいて再度お試しください',
};
