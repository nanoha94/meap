<?php

use App\Http\Controllers\Api\AiRecipeController;
use App\Http\Controllers\Api\AiUsageController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\IngredientUnitController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\MealCategoryController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\RecipeCategoryController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\ShoppingCategoryController;
use App\Http\Controllers\Api\ShoppingItemController;
use App\Http\Controllers\Api\ShoppingTagController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

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
    Route::post('/images/groups/upload-bulk', [ImageController::class, 'bulkUploadForGroup']);
    Route::post('/images/users/upload', [ImageController::class, 'uploadForUser']);

    // ingredients
    Route::get('/ingredient-units', [IngredientUnitController::class, 'index']);

    // invitations
    Route::post('invitations', [InvitationController::class, 'store']);
    Route::get('invitations/{token}', [InvitationController::class, 'show']);
    Route::post('/invitations/{token}/join', [InvitationController::class, 'join']);

    // users
    Route::get('/users', [UserController::class, 'index']);

    // shopping
    Route::apiResource('/shopping-items', ShoppingItemController::class)->only(['index']);
    Route::post('/shopping-items/bulk', [ShoppingItemController::class, 'bulkStore']);
    Route::put('/shopping-items/bulk', [ShoppingItemController::class, 'bulkUpdate']);
    Route::delete('/shopping-items/bulk', [ShoppingItemController::class, 'bulkDestroy']);
    Route::apiResource('/shopping-categories', ShoppingCategoryController::class)->only(['index']);
    Route::post('/shopping-categories/bulk', [ShoppingCategoryController::class, 'bulkStore']);
    Route::put('/shopping-categories/bulk', [ShoppingCategoryController::class, 'bulkUpdate']);
    Route::delete('/shopping-categories/bulk', [ShoppingCategoryController::class, 'bulkDestroy']);
    Route::apiResource('/shopping-tags', ShoppingTagController::class)->only(['index']);

    // ai
    Route::get('/ai/usage', [AiUsageController::class, 'show']);
    Route::post('/ai/recipes/parse-img', [AiRecipeController::class, 'parseImage'])
        ->middleware('throttle:ai');
    Route::post('/ai/recipes/parse-url', [AiRecipeController::class, 'parseUrl'])
        ->middleware('throttle:ai');

    // billing
    Route::get('/billing/status', [BillingController::class, 'status']);
    Route::get('/billing/invoices', [BillingController::class, 'invoices']);
    Route::post('/billing/subscribe/{subscriptionType}', [BillingController::class, 'subscribe'])
        ->where('subscriptionType', 'standard');
    Route::post('/billing/portal', [BillingController::class, 'portal']);
    Route::post('/billing/subscription/resume', [BillingController::class, 'resume']);
    Route::post('/billing/packs/{packType}', [BillingController::class, 'purchasePack'])
        ->where('packType', 'light|value');

    // master
    Route::get('/master', MasterController::class);
});
