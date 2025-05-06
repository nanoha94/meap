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


    Route::get('/group/shopping/items', [ShoppingItemController::class, 'index'])
        ->name('group.shopping.items.index');

    Route::post('/group/shopping/items', [ShoppingItemController::class, 'storeOrUpdate'])
        ->name('group.shopping.items.storeOrUpdate');

    Route::delete('/group/shopping/items', [ShoppingItemController::class, 'destroy'])
        ->name('group.shopping.items.destroy');

    Route::delete('/group/shopping/items/all', [ShoppingItemController::class, 'destroyAll'])
        ->name('group.shopping.items.destroyAll');

    Route::get('/group/shopping/categories', [ShoppingCategoryController::class, 'index'])->name('group.shopping.categories.index');

    Route::post('/group/shopping/categories', [ShoppingCategoryController::class, 'storeOrUpdate'])
        ->name('group.shopping.categories.storeOrUpdate');

    Route::delete('/group/shopping/categories', [ShoppingCategoryController::class, 'destroy'])
        ->name('group.shopping.categories.destroy');
});
