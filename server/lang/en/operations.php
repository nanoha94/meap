<?php

return [
    'recipe' => [
        'index' => 'Recipe list retrieval',
        'store' => 'Recipe creation',
        'show' => 'Recipe detail retrieval',
        'update' => 'Recipe update',
        'destroy' => 'Recipe deletion',
    ],
    'meal_plan' => [
        'index' => 'Meal plan list retrieval',
        'store' => 'Meal plan creation',
        'show' => 'Meal plan detail retrieval',
        'update' => 'Meal plan update',
        'destroy' => 'Meal plan deletion',
    ],
    'meal_type' => [
        'store' => 'Meal type creation',
        'bulk_update' => 'Meal type bulk update',
        'destroy' => 'Meal type deletion',
    ],
    'image' => [
        'upload_bulk' => 'Bulk image upload',
        'delete_bulk' => 'Bulk image deletion',
    ],
    'invitation' => [
        'store' => 'Invitation token creation',
        'show' => 'Invitation token detail retrieval',
        'join' => 'Group joining',
    ],
    'ingredient_category' => [
        'index' => 'Ingredient category list retrieval',
        'store' => 'Ingredient category creation',
        'bulk_update' => 'Ingredient category bulk update',
        'bulk_destroy' => 'Ingredient category bulk deletion',
    ],
    'shopping_category' => [
        'index' => 'Shopping category list retrieval',
        'store' => 'Shopping category creation',
        'bulk_update' => 'Shopping category bulk update',
        'bulk_destroy' => 'Shopping category bulk deletion',
    ],
    'shopping_item' => [
        'index' => 'Shopping item list retrieval',
        'store' => 'Shopping item creation',
        'bulk_update' => 'Shopping item bulk update',
        'bulk_destroy' => 'Shopping item bulk deletion',
        'tag_processing' => 'Shopping item tag processing',
    ],
    'recipe_category' => [
        'store' => 'Recipe category creation',
        'bulk_update' => 'Recipe category bulk update',
        'bulk_destroy' => 'Recipe category bulk deletion',
    ],
    'auth' => [
        'email_verification' => 'Email verification process',
        'registration' => 'User registration',
    ],
    'general' => [
        'exception_handling' => 'Exception handling',
    ],
    'users' => [
        'index' => 'User list retrieval',
    ],

    'master' => [
        'index' => 'Master data retrieval',
    ],

    'shopping_tag' => [
        'index' => 'Shopping tag list retrieval',
    ],
];
