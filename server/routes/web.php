<?php

use App\Http\Controllers\SquareWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('square/webhook', [SquareWebhookController::class, 'handle'])
    ->name('square.webhook');

require __DIR__ . '/auth.php';
