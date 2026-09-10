import { EMAIL_VERIFY_ERROR_MESSAGES, LINK_TO } from '@/constants';

/**
 * メール認証エラーコードをクエリ文字列から取得（許可リストのみ）
 */
export function getEmailVerifyErrorFromSearch(
    search: string | undefined,
): string | null {
    if (search == null || search === '') {
        return null;
    }
    const normalizedSearch = search.startsWith('?') ? search.slice(1) : search;
    const errorCode = new URLSearchParams(normalizedSearch).get('error');
    if (errorCode == null || !(errorCode in EMAIL_VERIFY_ERROR_MESSAGES)) {
        return null;
    }
    return errorCode;
}

/** 未ログイン時の /login リダイレクト URL（許可された ?error= のみ引き継ぐ） */
export function buildLoginRedirectUrl(search: string | undefined): string {
    const emailVerifyError = getEmailVerifyErrorFromSearch(search);
    if (emailVerifyError) {
        return `${LINK_TO.LOGIN}?error=${encodeURIComponent(emailVerifyError)}`;
    }
    return LINK_TO.LOGIN;
}
