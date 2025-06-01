<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        $baseUrl = config('app.url');

        // ローカル環境の場合、ポート番号を追加
        if (app()->environment('local')) {
            $baseUrl = 'https://localhost:8000';
        }

        $temporarySignedURL = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        // URLのベース部分を置き換え
        $url = str_replace(url('/'), $baseUrl, $temporarySignedURL);

        Log::debug('Generated verification URL', [
            'base_url' => $baseUrl,
            'url' => $url
        ]);

        return $url;
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
