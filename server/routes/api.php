<?php

use App\Http\Controllers\Api\GroupUsersController as ApiGroupUsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvitationTokenController;
use App\Http\Controllers\Api\ShoppingCategoryController;
use App\Http\Controllers\Api\ShoppingItemController;
use App\Models\ShoppingCategory;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth')->group(function () {
    Route::get('/invitation', [InvitationTokenController::class, 'store'])
        ->name('invitation.request');

    Route::get('/invitation/token/{token}', [InvitationTokenController::class, 'show'])
        ->name('invitation.token.details');

    Route::get('/group/users', [ApiGroupUsersController::class, 'index'])
        ->name('group.users.index');

    Route::post('/group/users/join/{token}', [ApiGroupUsersController::class, 'join'])
        ->name('group.users.join');

    Route::get('/group/shopping/items', [ShoppingItemController::class, 'index'])
        ->name('group.shopping.items.index');

    Route::post('/group/shopping/items', [ShoppingItemController::class, 'storeOrUpdate'])
        ->name('group.shopping.items.storeOrUpdate');

    Route::delete('/group/shopping/items', [ShoppingItemController::class, 'destroy'])
        ->name('group.shopping.items.destroy');

    Route::get('/group/shopping/categories', [ShoppingCategoryController::class, 'index'])->name('group.shopping.categories.index');

    Route::post('/group/shopping/categories', [ShoppingCategoryController::class, 'storeOrUpdate'])
        ->name('group.shopping.categories.storeOrUpdate');

    Route::delete('/group/shopping/categories', [ShoppingCategoryController::class, 'destroy'])
        ->name('group.shopping.categories.destroy');
});
