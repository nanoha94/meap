<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Auth\BaseAuthRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class EmailVerificationRequest extends BaseAuthRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // 未認証ユーザーのチェック
        if (!$this->user()) {
            throw new HttpResponseException(
                redirect(config('app.frontend_url') . '/email/verify?error=unauthenticated')
            );
        }

        // ユーザーIDの検証
        if (!hash_equals((string) $this->user()->getKey(), (string) $this->route('id'))) {
            throw new HttpResponseException(
                redirect(config('app.frontend_url') . '/email/verify?error=invalid_link')
            );
        }

        // ハッシュの検証
        if (!hash_equals(sha1($this->user()->getEmailForVerification()), (string) $this->route('hash'))) {
            throw new HttpResponseException(
                redirect(config('app.frontend_url') . '/email/verify?error=invalid_link')
            );
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    /**
     * Fulfill the email verification request.
     *
     * @return void
     */
    public function fulfill()
    {
        if (!$this->user()->hasVerifiedEmail()) {
            $this->user()->markEmailAsVerified();

            event(new Verified($this->user()));
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return \Illuminate\Validation\Validator
     */
    public function withValidator(Validator $validator)
    {
        return $validator;
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.auth.email_verification');
    }
}
