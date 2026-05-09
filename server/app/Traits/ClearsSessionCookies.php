<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ClearsSessionCookies
{
    /**
     * レスポンスにセッション・CSRF用Cookieの削除を付与する。
     * ログアウトやアカウント削除時に、複数のドメイン・パスパターンで削除する。
     */
    protected function clearSessionCookiesOnResponse(JsonResponse $response): JsonResponse
    {
        $domains = [
            config('session.domain'),
            null,
            '',
            '.' . parse_url(config('app.url'), PHP_URL_HOST),
        ];
        $paths = [config('session.path'), '/', ''];

        foreach ($domains as $domain) {
            foreach ($paths as $path) {
                $response->cookie(
                    config('session.cookie'),
                    '',
                    -1,
                    $path,
                    $domain,
                    config('session.secure'),
                    config('session.http_only'),
                    false,
                    config('session.same_site')
                );

                $response->cookie(
                    'XSRF-TOKEN',
                    '',
                    -1,
                    $path,
                    $domain,
                    config('session.secure'),
                    false,
                    false,
                    config('session.same_site')
                );
            }
        }

        return $response;
    }
}
