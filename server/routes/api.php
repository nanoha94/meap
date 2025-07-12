<?php

use App\Http\Controllers\Api\DishCategoryController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\GroupUsersController as ApiGroupUsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\ShoppingCategoryController;
use App\Http\Controllers\Api\ShoppingItemController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\ShoppingTagController;
use App\Http\Controllers\Api\MealCategoryController;


Route::middleware(['auth:sanctum'])->group(function () {
    // meals
    Route::apiResource('/meals', MealController::class);
    Route::apiResource('/meals/categories', MealCategoryController::class)->only(['store', 'destroy']);
    Route::put('/meals/categories/bulk', [MealCategoryController::class, 'bulkUpdate']);

    // dishes
    Route::apiResource('/dishes', DishController::class);
    Route::apiResource('/dishes/categories', DishCategoryController::class)->except(['index', 'show']);

    // invitations
    Route::resource('invitations', InvitationController::class)->only(['store', 'show']);
    Route::post('/invitations/{token}/join', [InvitationController::class, 'join']);

    // users
    Route::get('/users', [ApiGroupUsersController::class, 'index']);
    Route::get('/user',  function (Request $request) {
        $user = $request->user();
        return [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'avatar_seed' => $user->avatar_seed,
        ];
    });

    // shopping
    Route::apiResource('/shopping/items', ShoppingItemController::class)->only(['index', 'store']);
    Route::put('/shopping/items/bulk', [ShoppingItemController::class, 'bulkUpdate']);
    Route::delete('/shopping/items/bulk', [ShoppingItemController::class, 'bulkDestroy']);
    Route::apiResource('/shopping/categories', ShoppingCategoryController::class)->only(['index', 'store']);
    Route::put('/shopping/categories/bulk', [ShoppingCategoryController::class, 'bulkUpdate']);
    Route::delete('/shopping/categories/bulk', [ShoppingCategoryController::class, 'bulkDestroy']);
    Route::apiResource('/shopping/tags', ShoppingTagController::class)->only(['index']);

    // master
    Route::get('/master', MasterController::class);
});
