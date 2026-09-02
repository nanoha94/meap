<?php

return [
    'general' => [
        'unauthenticated' => '認証が必要です。',
        'already_logged_in' => '既にログインしています。',

    ],

    'success' => ':attributeに成功しました。',
    'failed' => ':attributeに失敗しました。',
    'throttle' => '試行回数が上限に達しました。:seconds秒後に再度お試しください。',

    'login' => [
        'warning' => 'メールアドレスまたはパスワードが正しくありません。',
    ],

    'register' => [
        'failed' => '登録に失敗しました。入力内容をご確認ください。',
    ],

    'email_verification' => [
        'failed' => 'メールアドレス確認の処理に失敗しました。',
        'store_failed' => 'メールアドレス確認リンクの再送に失敗しました。',
        'store_sent' => '登録時に入力されたメールアドレス宛にメールアドレス確認リンクを再送しました。',
        'store_not_found' => '未登録のメールアドレスです。',
    ],

    'attributes' => [
        'login' => 'ログイン',
        'logout' => 'ログアウト',
        'register' => 'ユーザー登録',
    ],
];
