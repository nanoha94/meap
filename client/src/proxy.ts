import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

import { LINK_TO } from '@/constants';
import { normalizePathnameForMatch } from '@/utils/pathname';

// (auth) シェル配下の RSC レイアウトが現在のパスを参照できるよう、
// リクエストヘッダに正規化済み x-pathname を付与してそのまま通過させる
const forwardWithPathnameHeader = (request: NextRequest) => {
    const requestHeaders = new Headers(request.headers);
    requestHeaders.set(
        'x-pathname',
        normalizePathnameForMatch(request.nextUrl.pathname),
    );
    return NextResponse.next({ request: { headers: requestHeaders } });
};

export function proxy(request: NextRequest) {
    const baseUrl = process.env.NEXT_PUBLIC_FRONTEND_URL;
    const pathname = request.nextUrl.pathname;
    const searchParams = request.nextUrl.search;
    const token = request.nextUrl.searchParams.get('token');

    const hasAuthCookie =
        request.cookies.has('laravel_session') ||
        request.cookies.has('XSRF-TOKEN') ||
        request.cookies
            .getAll()
            .some((c) => c.name.startsWith('remember_web_'));

    // /settings/account: 招待トークンがある場合のみ、未ログイン時のリダイレクト処理を行う
    if (pathname === LINK_TO.SETTINGS.ACCOUNT) {
        if (!hasAuthCookie) {
            const response = NextResponse.redirect(
                new URL(LINK_TO.LOGIN, baseUrl),
            );

            // /settings/account?token=XXXの場合のみリダイレクトパスをCookieに設定
            if (token) {
                const redirectPath = `${pathname}${searchParams}`;
                response.cookies.set('redirectPath', redirectPath, {
                    path: '/',
                    maxAge: 3600, // 1時間有効
                    sameSite: 'lax', // メール認証後バックエンド→フロントのリダイレクト時にも送るため
                    secure: true,
                });
            }

            return response;
        }

        // ログイン済み: リクエストヘッダーを引き継いでそのまま通過
        return forwardWithPathnameHeader(request);
    }

    // (auth) 配下のパス（matcher で限定）: AuthLayout から headers() で参照する
    // 正規化済み x-pathname を付与してそのまま通す。リダイレクト判定は AuthLayout 側で
    // handleAuthRedirect に集約する。
    return forwardWithPathnameHeader(request);
}

export const config = {
    matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
