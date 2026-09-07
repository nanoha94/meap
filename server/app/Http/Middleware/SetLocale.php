<?php

namespace App\Http\Middleware;

use App\Helpers\LocalizationHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 認証済みユーザーの場合、ユーザーの言語設定を優先
        if ($request->user()) {
            LocalizationHelper::setLocaleFromUser($request->user());
        } else {
            // リクエストヘッダーからロケールを設定
            LocalizationHelper::setLocaleFromRequest($request);
        }

        return $next($request);
    }
}
