<?php

return [
    'auth' => [
        'login' => 'Login',
        'register' => 'User registration',
        'register_user' => 'User registration',
        'email_verification_notification' => 'Resend email verification link',
        'email_verification' => 'Email verification process',
        'password_reset' => 'Password reset',
        'password_reset_link' => 'Send password reset link',
    ],
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
        'destroy_meal' => 'Delete one meal from meal plan',
    ],
    'meal_category' => [
        'index' => 'Meal category list retrieval',
        'bulk_store' => 'Meal category bulk creation',
        'bulk_update' => 'Meal category bulk update',
        'bulk_destroy' => 'Meal category bulk deletion',
    ],
    'image' => [
        'bulk_upload' => 'Bulk image upload',
        'bulk_destroy' => 'Bulk image deletion',
        'delete_images_by_group' => 'Delete images by group',
        'delete_images_by_user' => 'Delete images by user',
    ],
    'invitation' => [
        'store' => 'Invitation token creation',
        'show' => 'Invitation token detail retrieval',
        'join' => 'Group joining',
    ],
    'ingredient_category' => [
        'index' => 'Ingredient category list retrieval',
        'bulk_store' => 'Ingredient category bulk creation',
        'bulk_update' => 'Ingredient category bulk update',
        'bulk_destroy' => 'Ingredient category bulk deletion',
    ],
    'shopping_category' => [
        'index' => 'Shopping category list retrieval',
        'bulk_store' => 'Shopping category bulk creation',
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
        'index' => 'Recipe category list retrieval',
        'bulk_store' => 'Recipe category bulk creation',
        'bulk_update' => 'Recipe category bulk update',
        'bulk_destroy' => 'Recipe category bulk deletion',
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

    'general' => [
        'unknown' => 'Unknown operation',
        'request' => 'Request processing',
    ],
];
