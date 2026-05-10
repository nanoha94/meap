import { redirect } from 'next/navigation';

import { INTERNAL_LINKS, SESSION_REQUIRED_PATHS_IN_AUTH_SHELL } from '@/constants';
import { ILoginUser } from '@/types';
import { normalizePathnameForMatch } from '@/utils/pathname';

export interface HandleAuthRedirectOptions {
    /** 現在のパス（通常は middleware の x-pathname。未指定・空なら (auth) シェル内のセッション必須特例は適用しない。正規化は本モジュールで行う） */
    pathname?: string;
}

/**
 * (auth) シェル内でもセッション必須とするパスかどうかを判定
 * @param pathname 現在のパス（通常は middleware の x-pathname。未指定・空なら (auth) シェル内のセッション必須特例は適用しない）
 * @returns セッション必須パスかどうか
 */
function pathnameRequiresSessionInAuthShell(pathname: string | undefined): boolean {
    if (pathname == null || pathname === '') {
        return false;
    }
    const normalized = normalizePathnameForMatch(pathname);
    return SESSION_REQUIRED_PATHS_IN_AUTH_SHELL.some(
        (prefix) =>
            normalized === prefix || normalized.startsWith(`${prefix}/`),
    );
}

/**
 * 認証状態に基づいてリダイレクト
 * @param user ユーザー情報
 * @param isAuthPage 認証ページかどうか
 * @param options `(auth)` シェルで pathname が判明するとき、一部ルートは未ログインでもログインへ送る
 */
export function handleAuthRedirect(
    user: ILoginUser | null,
    isAuthPage: boolean = false,
    options?: HandleAuthRedirectOptions,
): void {
    if (user) {
        if (user.email_verified_at && isAuthPage) {
            // 認証済みユーザーが認証ページにアクセスした場合
            redirect('/plan');
        }
        // メール未認証ユーザーが保護されたページにアクセスした場合のみ
        else if (!user.email_verified_at && !isAuthPage) {
            redirect('/email/verify');
        }
        // メール未認証ユーザーが認証ページにアクセスした場合は何もしない
    } else {
        // 未ログインユーザーが保護されたページにアクセスした場合
        if (!isAuthPage) {
            redirect(INTERNAL_LINKS.LOGIN);
        }
        // (auth) シェル内でもセッション必須とするパスにアクセスした場合
        else if (pathnameRequiresSessionInAuthShell(options?.pathname)) {
            redirect(INTERNAL_LINKS.LOGIN);
        }
    }
}
