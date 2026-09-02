<?php

namespace App\Http\Requests\Auth;

use App\Enums\HttpStatusCode;
use App\Http\Requests\Auth\BaseAuthRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginRequest extends BaseAuthRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('validation.required', ['attribute' => 'email']),
            'email.string' => __('validation.string', ['attribute' => 'email']),
            'email.email' => __('validation.email', ['attribute' => 'email']),
            'password.required' => __('validation.required', ['attribute' => 'password']),
            'password.string' => __('validation.string', ['attribute' => 'password']),
            'password.max' => __('validation.max.string', ['attribute' => 'password', 'max' => 255]),
            'remember.boolean' => __('validation.boolean', ['attribute' => 'remember']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.auth.login');
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // 認証失敗時にレートリミットを設定
            $decaySeconds = (int) config('auth.login.decay_seconds', 60);
            RateLimiter::hit($this->throttleKey(), $decaySeconds);
            RateLimiter::hit($this->ipThrottleKey(), $decaySeconds);

            // 認証失敗をExceptionHandlerTraitで処理
            $authException = new HttpException(HttpStatusCode::UNAUTHORIZED->value, __('auth.login.warning'));

            $response = $this->handleException(
                $authException,
                $this,
                __('auth.login.warning'),
                __('operations.auth.login')
            );

            throw new HttpResponseException($response);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->ipThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $decaySeconds = (int) config('auth.login.decay_seconds', 60);
        $limits = [
            ['key' => $this->throttleKey(), 'max' => (int) config('auth.login.max_attempts', 5)],
            ['key' => $this->ipThrottleKey(), 'max' => (int) config('auth.login.ip_max_attempts', 20)],
        ];

        foreach ($limits as $limit) {
            if (! RateLimiter::tooManyAttempts($limit['key'], $limit['max'])) {
                continue;
            }

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($limit['key']);
            $displaySeconds = min($decaySeconds, $seconds);

            $response = $this->handleException(
                new ThrottleRequestsException(__('auth.throttle', ['seconds' => $displaySeconds])),
                $this,
                __('auth.throttle', ['seconds' => $displaySeconds]),
                __('operations.auth.login')
            );

            throw new HttpResponseException($response);
        }
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')) . '|' . $this->ip());
    }

    /**
     * Get the IP-only rate limiting throttle key for the request.
     */
    public function ipThrottleKey(): string
    {
        return 'login|ip|' . $this->ip();
    }
}
