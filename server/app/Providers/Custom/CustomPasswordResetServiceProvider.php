<?php

namespace App\Providers\Custom;

use App\Custom\Auth\CustomPasswordBrokerManager;
use App\Custom\Auth\Interfaces\CustomPasswordBroker;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;
use Illuminate\Contracts\Auth\PasswordBroker;

class CustomPasswordResetServiceProvider extends PasswordResetServiceProvider
{

    public function register()
    {
        // カスタマイズしたPasswordBrokerインターフェイスを使用
        $this->app->bind(PasswordBroker::class, CustomPasswordBroker::class);

        $this->registerPasswordBroker();
    }

    /**
     * CustomPasswordBrokerManagerを指定する
     *
     * @return void
     */
    protected function registerPasswordBroker()
    {
        $this->app->singleton('auth.password', function ($app) {
            return new CustomPasswordBrokerManager($app);
        });

        $this->app->bind('auth.password.broker', function ($app) {
            return $app->make('auth.password')->broker();
        });
    }
}
