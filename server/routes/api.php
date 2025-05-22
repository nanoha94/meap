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
use App\Http\Controllers\Api\PageShoppingListController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth')->group(function () {
    // resource
    Route::apiResource('/meals', MealController::class);
    Route::apiResource('/dishes', DishController::class);
    Route::apiResource('/dishes/caategories', DishCategoryController::class)->except(['index', 'show']);
    Route::resource('invitations', InvitationController::class)->only(['store', 'show']);
    Route::post('/invitations/{token}/join', [InvitationController::class, 'join']);
    Route::get('/users', [ApiGroupUsersController::class, 'index']);
    Route::apiResource('/shopping/items', ShoppingItemController::class)->except(['show']);
    Route::apiResource('/shopping/categories', ShoppingCategoryController::class)->except(['show']);

    // page
    Route::get('/page/shopping-list', PageShoppingListController::class);

    // master
    Route::get('/master', MasterController::class);
});
