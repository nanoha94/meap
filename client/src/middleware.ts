import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
    const baseUrl = process.env.NEXT_PUBLIC_FRONT_URL;
    const pathname = request.nextUrl.pathname;
    const searchParams = request.nextUrl.search;
    const token = request.nextUrl.searchParams.get('token');

    const hasAuthCookie =
        request.cookies.has('laravel_session') ||
        request.cookies.has('XSRF-TOKEN');

    // 条件なしにリダイレクトパスをセット
    const response = !hasAuthCookie
        ? NextResponse.redirect(new URL('/login', baseUrl))
        : NextResponse.next();

    // /settings/account?token=XXXの場合はリダイレクトパスをCookieに設定
    if (token) {
        response.cookies.set('redirectPath', `${pathname}${searchParams}`, {
            path: '/',
            maxAge: 3600, // 1時間有効
            sameSite: 'strict',
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
