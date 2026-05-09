import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function proxy(request: NextRequest) {
    const baseUrl = process.env.NEXT_PUBLIC_FRONT_URL;
    const pathname = request.nextUrl.pathname;
    const searchParams = request.nextUrl.search;
    const token = request.nextUrl.searchParams.get('token');

    const redirectPath = `${pathname}${searchParams}`;

    const hasAuthCookie =
        request.cookies.has('laravel_session') ||
        request.cookies.has('XSRF-TOKEN');

    // 条件なしにリダイレクトパスをセット
    const response = !hasAuthCookie
        ? NextResponse.redirect(new URL('/login', baseUrl))
        : NextResponse.next();

    // /settings/account?token=XXXの場合で未承認の場合のみリダイレクトパスをCookieに設定
    if (token && !hasAuthCookie) {
        response.cookies.set('redirectPath', redirectPath, {
            path: '/',
            maxAge: 3600, // 1時間有効
            sameSite: 'lax', // メール認証後バックエンド→フロントのリダイレクト時にも送るため
            secure: true,
        });
    }

    return response;
}

// 招待トークンがある場合のみ、未ログイン時のリダイレクト処理を行う
// その他は、AuthLayout/AppLayoutで認証チェックして適宜リダイレクトする
export const config = {
    matcher: ['/settings/account'],
};
