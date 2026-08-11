import { LINK_TO } from './links';

/**
 * (auth) レイアウト配下でもセッション必須とするパス（ゲスト許可の例外）
 */
export const SESSION_REQUIRED_PATHS_IN_AUTH_SHELL = [
    LINK_TO.EMAIL_VERIFY,
] as const;

export const OAUTH_ERROR_MESSAGES: Record<string, string> = {
    oauth_state_invalid:
        'セッションの有効期限が切れました。もう一度お試しください。',
    oauth_failed: 'Google認証に失敗しました。もう一度お試しください。',
    oauth_no_email:
        'Googleアカウントからメールアドレスを取得できませんでした。',
    oauth_email_unverified:
        '同じメールアドレスのアカウントが未認証のため、Google連携できません。メール認証後に再度お試しください。',
};