<?php

use App\Http\Controllers\PayjpWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('payjp/webhook', [PayjpWebhookController::class, 'handle'])
    ->name('payjp.webhook');

require __DIR__ . '/auth.php';
