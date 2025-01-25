<?php

namespace App\Custom\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class CustomDatabaseTokenRepository extends DatabaseTokenRepository
{
    /**
     * 生成したトークンが重複していないかチェックする処理を追加
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return string
     */
    public function create(CanResetPasswordContract $user)
    {
        $maxAttempts = 5; // 最大試行回数
        $attempt = 0;

        $email = $user->getEmailForPasswordReset();

        $this->deleteExisting($user);

        do {
            // We will create a new, random token for the user so that we can e-mail them
            // a safe link to the password reset form. Then we will insert a record in
            // the database so that we can verify the token within the actual reset.
            $token = $this->createNewToken();

            $attempt++;
            if ($attempt >= $maxAttempts) {
                return null; // トークン生成に失敗した場合は null を返す
            }
        } while ($this->getTable()->where('token', $token)->exists());

        $this->getTable()->insert($this->getPayload($email, $token));

        return $token;
    }
}
