<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPassword
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('パスワード再設定')
            ->greeting($notifiable->name . ' 様')
            ->line('パスワード再設定のリクエストを受け付けました。以下のボタンをクリックして、新しいパスワードを設定してください。')
            ->action('パスワードを再設定', $url)
            ->line('このリンクの有効期限は ' . config('auth.passwords.' . config('auth.defaults.passwords') . '.expire') . ' 分です。')
            ->line('心当たりがない場合は、このメールを無視してください。');
    }
}
