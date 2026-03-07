<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\IngredientCategoryController;
use App\Http\Controllers\Api\IngredientUnitController;
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
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user', [UserController::class, 'update']);
    Route::delete('/user', [UserController::class, 'destroy']);
});

// メール認証済みユーザーのみアクセス可能
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/meal-plans/{date}', [MealPlanController::class, 'show']);
    Route::apiResource('meal-plans', MealPlanController::class)->except(['show']);
    Route::delete('/meal-plans/{mealPlanId}/meals/{mealId}', [MealPlanController::class, 'destroyMeal'])
        ->name('meal-plans.meals.destroy');
    Route::apiResource('/meal-categories', MealCategoryController::class)->only(['index']);
    Route::post('/meal-categories/bulk', [MealCategoryController::class, 'bulkStore']);
    Route::put('/meal-categories/bulk', [MealCategoryController::class, 'bulkUpdate']);
    Route::delete('/meal-categories/bulk', [MealCategoryController::class, 'bulkDestroy']);

    // recipes
    Route::apiResource('/recipes', RecipeController::class);
    Route::apiResource('/recipe-categories', RecipeCategoryController::class)->only(['index']);
    Route::post('/recipe-categories/bulk', [RecipeCategoryController::class, 'bulkStore']);
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
    Route::get('/ingredient-units', [IngredientUnitController::class, 'index']);

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
