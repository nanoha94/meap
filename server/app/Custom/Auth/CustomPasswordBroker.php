<?php

namespace App\Custom\Auth;

use App\Custom\Auth\Interfaces\CustomPasswordBroker as InterfacesCustomPasswordBroker;
use App\Models\User;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Closure;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
     * トークンからメールアドレスを取得して、それを使ってパスワードリセット処理を実行する
     *
     * @param  array  $credentials
     * @param  \Closure  $callback
     * @return mixed
     */
    public function reset(#[\SensitiveParameter] array $credentials, Closure $callback)
    {
        // トークンからメールアドレスを取得
        $token = $credentials['token'];
        $passwordResets = DB::table(config('auth.passwords.users.table'))
            ->where('created_at', '>=', now()->subMinutes(config('auth.passwords.users.expire')))
            ->get()
            ->filter(
                function ($record) use ($token) {
                    return Hash::check($token, $record->token);
                }
            );

        // usersテーブルに該当のメールアドレスが存在するかチェック
        $isExistUser = User::where('email', $passwordResets->first()->email)->exists();

        // トークンが有効期限内でない、もしくはトークンに一致するメールアドレスがない場合
        if ($passwordResets->count() !== 1 || !$isExistUser) {
            return static::INVALID_TOKEN;
        }

        // $credentialsにメールアドレスの情報を含める
        $credentials['email'] = $passwordResets->first()->email;

        $user = $this->validateReset($credentials);

        // If the responses from the validate method is not a user instance, we will
        // assume that it is a redirect and simply return it from this method and
        // the user is properly redirected having an error message on the post.
        if (! $user instanceof CanResetPasswordContract) {
            return $user;
        }

        $password = $credentials['password'];

        // Once the reset has been validated, we'll call the given callback with the
        // new password. This gives the user an opportunity to store the password
        // in their persistent storage. Then we'll delete the token and return.
        $callback($user, $password);

        $this->tokens->delete($user);

        return static::PASSWORD_RESET;
    }
}
