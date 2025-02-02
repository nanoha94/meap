<?php

use App\Http\Controllers\InvitationTokenAcceptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/invitation/{token}', InvitationTokenAcceptController::class)
    ->name('invitation.accept');


require __DIR__ . '/auth.php';
