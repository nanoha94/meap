<?php

use App\Http\Controllers\Api\GroupUsersController as ApiGroupUsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\ShoppingCategoryController;
use App\Http\Controllers\Api\ShoppingItemController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth')->group(function () {
    Route::resource('invitations', InvitationController::class)->only(['store', 'show']);
    Route::post('/invitations/{token}/join', [InvitationController::class, 'join']);

    Route::get('/users', [ApiGroupUsersController::class, 'index']);

    Route::apiResource('/shopping/items', ShoppingItemController::class)->except(['show']);

    Route::apiResource('/shopping/categories', ShoppingCategoryController::class)->except(['show']);
});
