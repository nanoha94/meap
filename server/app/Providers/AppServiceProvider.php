<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ローカル環境での設定
        if (app()->environment('local')) {
            // ベースURLをポート番号なしで設定
            URL::forceRootUrl('https://localhost');
        } else {
            // 本番環境などでの設定
            URL::forceRootUrl(Config::get('app.url'));
        }
        URL::forceScheme('https');

        // パスワードリセットURLの設定
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password/reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
