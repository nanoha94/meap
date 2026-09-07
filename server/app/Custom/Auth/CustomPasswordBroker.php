<?php

namespace App\Custom\Auth;

use App\Custom\Auth\Interfaces\CustomPasswordBroker as InterfacesCustomPasswordBroker;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Closure;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Passwords\PasswordBroker;

class CustomPasswordBroker extends PasswordBroker implements InterfacesCustomPasswordBroker
{

    /**
     * トークン発行に失敗した時に返すステートをカスタマイズ
     *
     * @param  array  $credentials
     * @param  \Closure|null  $callback
     * @return string
     */
    public function sendResetLink(#[\SensitiveParameter] array $credentials, ?Closure $callback = null)
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        $user = $this->getUser($credentials);

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        if ($this->tokens->recentlyCreatedToken($user)) {
            return static::RESET_THROTTLED;
        }

        $token = $this->tokens->create($user);

        // トークン生成に失敗した場合
        if (is_null($token)) {
            return static::RETRY_TOKEN;
        }

        if ($callback) {
            return $callback($user, $token) ?? static::RESET_LINK_SENT;
        }

        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to reset their password. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        $user->sendPasswordResetNotification($token);

        $this->events?->dispatch(new PasswordResetLinkSent($user));

        return static::RESET_LINK_SENT;
    }

    /**
     * メールアドレスでトークンを1件取得し、パスワードリセット処理を実行する
     *
     * @param  array  $credentials
     * @param  \Closure  $callback
     * @return mixed
     */
    public function reset(#[\SensitiveParameter] array $credentials, Closure $callback)
    {
        $user = $this->validateReset($credentials);

        if (! $user instanceof CanResetPasswordContract) {
            return $user;
        }

        $password = $credentials['password'];

        $callback($user, $password);

        $this->tokens->delete($user);

        return static::PASSWORD_RESET;
    }
}
