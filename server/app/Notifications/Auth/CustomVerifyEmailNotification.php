<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the verification URL for the given notifiable.
     *
     * 署名を相対パスで生成し（absolute: false）、APP_URL を先頭に付与してメールリンクにする。
     * ルート側は signed:relative で検証するため、scheme/host/port の不一致が発生しない。
     */
    protected function verificationUrl($notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        // 署名をパス（相対URL）に対して生成する
        $relativeSignedUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            absolute: false
        );

        // メールリンク用にフル URL を組み立てる
        return config('app.url') . $relativeSignedUrl;
    }

    /**
     * メール通知のメッセージを取得
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('メールアドレスの確認')
            ->greeting($notifiable->name . ' 様')
            ->line('ご登録ありがとうございます。以下のボタンをクリックしてメールアドレスの確認を完了してください。')
            ->action('メールアドレスを確認', $verificationUrl)
            ->line('このメールに心当たりがない場合は、このメールを無視してください。');
    }
}
