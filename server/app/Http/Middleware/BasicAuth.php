<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BASIC_AUTH_USER / BASIC_AUTH_PASSWORD が設定されているときのみ Basic 認証を有効化する
 */
class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // CORS プリフライト、ヘルスチェック、Stripe Webhook、Google OAuth は認証を除外
        if (
            $request->isMethod('OPTIONS')
            || $request->is('up')
            || $request->is('stripe/*')
            || $request->is('auth/google/*')
        ) {
            return $next($request);
        }

        $user = config('auth.basic_user');
        $password = config('auth.basic_password');

        if (!$user || !$password) {
            return $next($request);
        }

        if (
            ! hash_equals((string) $user, (string) ($request->getUser() ?? ''))
            || ! hash_equals((string) $password, (string) ($request->getPassword() ?? ''))
        ) {
            return response('Unauthorized', 401)
                ->header('WWW-Authenticate', 'Basic realm="Meap"');
        }

        return $next($request);
    }
}
