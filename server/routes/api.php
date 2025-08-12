<?php

use App\Http\Controllers\Api\CourseTypeController;
use App\Http\Controllers\Api\GroupUsersController as ApiGroupUsersController;
use App\Http\Controllers\Api\IngredientCategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\ShoppingCategoryController;
use App\Http\Controllers\Api\ShoppingItemController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\ShoppingTagController;
use App\Http\Controllers\Api\MealTypeController;
use App\Http\Controllers\Api\RecipeCategoryController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\ImageController;
use Illuminate\Routing\Router;

Route::middleware(['auth:sanctum'])->group(function () {
    // meal-plans
    Route::apiResource('/meal-plans', MealPlanController::class);
    Route::apiResource('/meal-types', MealTypeController::class)->only(['store', 'destroy']);
    Route::put('/meal-types/bulk', [MealTypeController::class, 'bulkUpdate']);
    Route::get('/course-types', [CourseTypeController::class, 'index']);

    // recipes
    Route::apiResource('/recipes', RecipeController::class);
    Route::apiResource('/recipe-categories', RecipeCategoryController::class)->only(['store']);
    Route::put('/recipe-categories/bulk', [RecipeCategoryController::class, 'bulkUpdate']);
    Route::delete('/recipe-categories/bulk', [RecipeCategoryController::class, 'bulkDestroy']);

    // images
    Route::post('/images/upload-bulk', [ImageController::class, 'uploadBulk']);
    Route::delete('/images/bulk', [ImageController::class, 'deleteBulk']);

    // ingredients
    Route::apiResource('/ingredient-categories', IngredientCategoryController::class)->only(['index', 'store']);
    Route::put('/ingredient-categories/bulk', [IngredientCategoryController::class, 'bulkUpdate']);
    Route::delete('/ingredient-categories/bulk', [IngredientCategoryController::class, 'bulkDestroy']);

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
    Route::apiResource('/shopping-items', ShoppingItemController::class)->only(['index', 'store']);
    Route::put('/shopping-items/bulk', [ShoppingItemController::class, 'bulkUpdate']);
    Route::delete('/shopping-items/bulk', [ShoppingItemController::class, 'bulkDestroy']);
    Route::apiResource('/shopping-categories', ShoppingCategoryController::class)->only(['index', 'store']);
    Route::put('/shopping-categories/bulk', [ShoppingCategoryController::class, 'bulkUpdate']);
    Route::delete('/shopping-categories/bulk', [ShoppingCategoryController::class, 'bulkDestroy']);
    Route::apiResource('/shopping-tags', ShoppingTagController::class)->only(['index']);

    // master
    Route::get('/master', MasterController::class);
});
