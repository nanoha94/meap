<?php

use App\Http\Controllers\Api\GroupUsersController as ApiGroupUsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvitationTokenController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth')->group(function () {
    Route::get('/invitation', [InvitationTokenController::class, 'store'])
        ->name('invitation.request');
});

Route::middleware('auth')->group(function () {
    Route::get('/group/users', [ApiGroupUsersController::class, 'index'])
        ->name('group.users.index');
});
