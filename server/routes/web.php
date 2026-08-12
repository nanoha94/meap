<?php

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

/**
 * TRUSTED_PROXIES 調査用（local / staging / develop のみ）。
 * remote_addr がプロキシ IP。確認後に削除してよい。
 */
Route::get('/debug/ip', function (Request $request) {
    abort_unless(app()->environment(['local', 'staging', 'develop']), 404);

    return [
        'remote_addr' => $request->server('REMOTE_ADDR'),
        'request_ip' => $request->ip(),
        'xff' => $request->header('X-Forwarded-For'),
        'x_real_ip' => $request->header('X-Real-IP'),
        'cf' => $request->header('CF-Connecting-IP'),
        'proto' => $request->header('X-Forwarded-Proto'),
        'secure' => $request->secure(),
        'trusted_proxies_env' => env('TRUSTED_PROXIES'),
    ];
});

require __DIR__ . '/auth.php';
