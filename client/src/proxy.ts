import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

import { LINK_TO } from '@/constants';
import { normalizePathnameForMatch } from '@/utils/pathname';

const unauthorizedBasicAuthResponse = () =>
    new NextResponse('Unauthorized', {
        status: 401,
        headers: {
            'WWW-Authenticate': 'Basic realm="Develop"',
        },
    });

/**
 * Basic認証を検証する
 */
const verifyBasicAuth = (request: NextRequest): NextResponse | null => {
    const user = process.env.NEXT_PUBLIC_BASIC_AUTH_USER;
    const password = process.env.NEXT_PUBLIC_BASIC_AUTH_PASSWORD;

    if (!user || !password) {
        return null;
    }

    // 認証情報が存在しない場合は401エラーを返す
    const authHeader = request.headers.get('authorization');
    if (!authHeader?.startsWith('Basic ')) {
        return unauthorizedBasicAuthResponse();
    }

    // 認証情報が不正な場合は401エラーを返す
    const decoded = Buffer.from(authHeader.slice(6), 'base64').toString(
        'utf-8',
    );
    const separatorIndex = decoded.indexOf(':');
    if (separatorIndex === -1) {
        return unauthorizedBasicAuthResponse();
    }

    // 認証情報が一致しない場合は401エラーを返す
    const providedUser = decoded.slice(0, separatorIndex);
    const providedPassword = decoded.slice(separatorIndex + 1);

    if (providedUser !== user || providedPassword !== password) {
        return unauthorizedBasicAuthResponse();
    }

    return null;
};

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
    const basicAuthResponse = verifyBasicAuth(request);
    if (basicAuthResponse) {
        return basicAuthResponse;
    }

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
