<?php

return [
    'login' => [
        'success' => 'ログインに成功しました。',
        'failed' => 'ログイン情報が存在しません。',
        'throttle' => 'ログイン試行回数が上限に達しました。:seconds秒後に再度お試しください。',
        'warning' => 'メールアドレスまたはパスワードが正しくありません。',
    ],
    'logout' => [
        'success' => 'ログアウトに成功しました。',
    ],
    'unauthenticated' => '認証が必要です。',
    'registration_success' => 'ユーザー登録に成功しました。',
    'registration_failed' => 'ユーザー登録に失敗しました。',
    'already_logged_in' => '既にログインしています。',
    'invitation_token_created' => '招待トークンを作成しました。',
    'invitation_token_creation_failed' => '招待トークンの作成に失敗しました。',

    'email_verification_failed' => 'メールアドレス確認の処理に失敗しました。',
    'invalid_verification_link' => 'メールアドレス確認リンクが無効です。新しい確認リンクを送信してください。',
    'email_verification_notification' => [
        'store_failed' => 'メールアドレス確認リンクの再送に失敗しました。',
        'store_sent' => '登録時に入力されたメールアドレス宛にメールアドレス確認リンクを再送しました。',
        'store_not_found' => '未登録のメールアドレスです。',
    ],
    'password' => [
        'reset_link' => 'パスワードリセットリンクの送信',
    ],


];
