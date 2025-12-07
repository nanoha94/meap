<?php

return [
    'auth' => [
        'login' => 'ログイン',
        'register' => 'ユーザー登録',
        'register_user' => 'ユーザー登録',
        'email_verification_notification' => 'メールアドレス確認リンクの再送信',
        'email_verification' => 'メール認証処理',
        'password_reset' => 'パスワードリセット',
        'password_reset_link' => 'パスワードリセットリンク送信',
    ],
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
    'meal_category' => [
        'index' => '献立カテゴリ一覧取得',
        'store' => '献立カテゴリ作成',
        'bulk_update' => '献立カテゴリ一括更新',
        'destroy' => '献立カテゴリ削除',
    ],
    'image' => [
        'bulk_upload' => '画像一括アップロード',
        'bulk_destroy' => '画像一括削除',
    ],
    'invitation' => [
        'store' => '招待トークン作成',
        'show' => '招待トークン詳細取得',
        'join' => 'グループ参加',
    ],
    'ingredient_category' => [
        'index' => '食材カテゴリー一覧取得',
        'bulk_store' => '食材カテゴリー一括作成',
        'bulk_update' => '食材カテゴリー一括更新',
        'bulk_destroy' => '食材カテゴリー一括削除',
    ],
    'shopping_category' => [
        'index' => '買い物カテゴリー一覧取得',
        'bulk_store' => '買い物カテゴリー一括作成',
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
    'users' => [
        'index' => 'ユーザー一覧取得',
    ],

    'master' => [
        'index' => 'マスターデータ取得',
    ],

    'shopping_tag' => [
        'index' => '買い物タグ一覧取得',
    ],

    'general' => [
        'unknown' => '不明な操作',
        'request' => 'リクエスト処理',
    ],
];
