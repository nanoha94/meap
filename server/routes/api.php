<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\IngredientCategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\ShoppingCategoryController;
use App\Http\Controllers\Api\ShoppingItemController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\ShoppingTagController;
use App\Http\Controllers\Api\MealCategoryController;
use App\Http\Controllers\Api\RecipeCategoryController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\ImageController;

// 認証のみ必要（メール認証不要）
Route::middleware(['auth:sanctum'])->group(function () {
    // 認証ユーザー情報を取得（メール未認証でもアクセス可能）
    Route::get('/user', [UserController::class, 'show']);
});

// メール認証済みユーザーのみアクセス可能
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // meal-plans
    Route::apiResource('/meal-plans', MealPlanController::class);
    Route::apiResource('/meal-categories', MealCategoryController::class)->only(['index', 'store', 'destroy']);
    Route::put('/meal-categories/bulk', [MealCategoryController::class, 'bulkUpdate']);

    // recipes
    Route::apiResource('/recipes', RecipeController::class);
    Route::apiResource('/recipe-categories', RecipeCategoryController::class)->only(['index', 'store']);
    Route::put('/recipe-categories/bulk', [RecipeCategoryController::class, 'bulkUpdate']);
    Route::delete('/recipe-categories/bulk', [RecipeCategoryController::class, 'bulkDestroy']);

    // images
    Route::post('/images/upload-bulk', [ImageController::class, 'bulkUpload']);
    Route::delete('/images/bulk', [ImageController::class, 'bulkDestroy']);

    // ingredients
    Route::apiResource('/ingredient-categories', IngredientCategoryController::class)->only(['index']);
    Route::post('/ingredient-categories/bulk', [IngredientCategoryController::class, 'bulkStore']);
    Route::put('/ingredient-categories/bulk', [IngredientCategoryController::class, 'bulkUpdate']);
    Route::delete('/ingredient-categories/bulk', [IngredientCategoryController::class, 'bulkDestroy']);

    // invitations
    Route::post('invitations', [InvitationController::class, 'store']);
    Route::get('invitations/{token}', [InvitationController::class, 'show']);
    Route::post('/invitations/{token}/join', [InvitationController::class, 'join']);

    // users
    Route::get('/users', [UserController::class, 'index']);

    // shopping
    Route::apiResource('/shopping-items', ShoppingItemController::class)->only(['index', 'store']);
    Route::put('/shopping-items/bulk', [ShoppingItemController::class, 'bulkUpdate']);
    Route::delete('/shopping-items/bulk', [ShoppingItemController::class, 'bulkDestroy']);
    Route::apiResource('/shopping-categories', ShoppingCategoryController::class)->only(['index']);
    Route::post('/shopping-categories/bulk', [ShoppingCategoryController::class, 'bulkStore']);
    Route::put('/shopping-categories/bulk', [ShoppingCategoryController::class, 'bulkUpdate']);
    Route::delete('/shopping-categories/bulk', [ShoppingCategoryController::class, 'bulkDestroy']);
    Route::apiResource('/shopping-tags', ShoppingTagController::class)->only(['index']);

    // master
    Route::get('/master', MasterController::class);
});
