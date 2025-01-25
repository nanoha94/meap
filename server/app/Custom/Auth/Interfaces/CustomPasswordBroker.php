<?php

namespace App\Custom\Auth\Interfaces;

use Illuminate\Contracts\Auth\PasswordBroker;

interface CustomPasswordBroker extends PasswordBroker
{
    /**
     * トークンの取得に失敗したことを表す定数
     *
     * @var string
     */
    const RETRY_TOKEN = 'passwords.retry_token';
}
