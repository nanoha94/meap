import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

import { LINK_TO } from '@/constants';
import { buildLoginRedirectUrl } from '@/utils/authHelpers';

export function proxy(request: NextRequest) {
    const baseUrl = process.env.NEXT_PUBLIC_FRONTEND_URL;
    const pathname = request.nextUrl.pathname;
    const searchParams = request.nextUrl.search;
    const token = request.nextUrl.searchParams.get('token');

    // API セッションではなく Cookie 有無で判定（期限切れ等は (session) 配下で再判定）
    const hasAuthCookie =
        request.cookies.has('laravel_session') ||
        request.cookies.has('XSRF-TOKEN') ||
        request.cookies
            .getAll()
            .some((c) => c.name.startsWith('remember_web_'));

    // /email/verify: 認証 Cookie なしなら /login へ（許可された ?error= のみ引き継ぐ）
    if (pathname === LINK_TO.EMAIL_VERIFY) {
        if (!hasAuthCookie) {
            return NextResponse.redirect(
                new URL(buildLoginRedirectUrl(searchParams), baseUrl),
            );
        }

        // (session)/layout.tsx は searchParams を読めないため、(session) ルートのフォールバック用
        const requestHeaders = new Headers(request.headers);
        requestHeaders.set('x-search', searchParams);
        return NextResponse.next({ request: { headers: requestHeaders } });
    }

    // /settings/account: 認証 Cookie なしなら /login へ（?token= がある場合のみ redirectPath Cookie を設定）
    if (pathname === LINK_TO.SETTINGS.ACCOUNT) {
        if (!hasAuthCookie) {
            const response = NextResponse.redirect(
                new URL(LINK_TO.LOGIN, baseUrl),
            );

            if (token) {
                const redirectPath = `${pathname}${searchParams}`;
                response.cookies.set('redirectPath', redirectPath, {
                    path: '/',
                    maxAge: 3600, // 1時間有効
                    sameSite: 'lax', // メール認証後バックエンド→フロントのリダイレクト時にも送るため
                    secure: true,
                    httpOnly: true,
                });
            }

            return response;
        }
    }

    return NextResponse.next();
}

export const config = {
    matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
