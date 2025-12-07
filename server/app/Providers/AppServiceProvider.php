<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        URL::forceRootUrl(Config::get('app.url'));
        URL::forceScheme('https');

        // パスワードリセットURLの設定
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password/reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // パスワードのバリデーションルールを設定
        Password::defaults(function () {
            return Password::min(8)
                ->letters() // 英字を含む
                ->numbers() // 数字を含む
                ->symbols(); // 記号を含む
        });
    }
}
