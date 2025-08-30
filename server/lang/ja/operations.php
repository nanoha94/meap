<?php

return [
    'recipe' => [
        'index' => 'レシピ一覧取得',
        'store' => 'レシピ作成',
        'show' => 'レシピ詳細取得',
        'update' => 'レシピ更新',
        'destroy' => 'レシピ削除',
    ],
    'meal_plan' => [
        'index' => '献立一覧取得',
        'store' => '献立作成',
        'show' => '献立詳細取得',
        'update' => '献立更新',
        'destroy' => '献立削除',
    ],
    'meal_type' => [
        'store' => '献立種別作成',
        'bulk_update' => '献立種別一括更新',
        'destroy' => '献立種別削除',
    ],
    'image' => [
        'upload_bulk' => '画像一括アップロード',
        'delete_bulk' => '画像一括削除',
    ],
    'invitation' => [
        'store' => '招待トークン作成',
        'show' => '招待トークン詳細取得',
        'join' => 'グループ参加',
    ],
    'ingredient_category' => [
        'index' => '食材カテゴリー一覧取得',
        'store' => '食材カテゴリー作成',
        'bulk_update' => '食材カテゴリー一括更新',
        'bulk_destroy' => '食材カテゴリー一括削除',
    ],
    'shopping_category' => [
        'index' => '買い物カテゴリー一覧取得',
        'store' => '買い物カテゴリー作成',
        'bulk_update' => '買い物カテゴリー一括更新',
        'bulk_destroy' => '買い物カテゴリー一括削除',
    ],
    'shopping_item' => [
        'index' => '買い物アイテム一覧取得',
        'store' => '買い物アイテム作成',
        'bulk_update' => '買い物アイテム一括更新',
        'bulk_destroy' => '買い物アイテム一括削除',
        'tag_processing' => '買い物アイテムタグ処理',
    ],
    'recipe_category' => [
        'index' => '料理カテゴリー一覧取得',
        'store' => '料理カテゴリー作成',
        'bulk_update' => '料理カテゴリー一括更新',
        'bulk_destroy' => '料理カテゴリー一括削除',
    ],
    'auth' => [
        'email_verification' => 'メール確認処理',
        'registration' => 'ユーザー登録',
    ],
    'users' => [
        'index' => 'ユーザー一覧取得',
    ],

    'master' => [
        'index' => 'マスターデータ取得',
    ],

    'shopping_tag' => [
        'index' => '買い物タグ一覧取得',
    ],
];
