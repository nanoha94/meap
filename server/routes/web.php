<?php

use App\Http\Controllers\InvitationTokenAcceptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});



require __DIR__ . '/auth.php';
