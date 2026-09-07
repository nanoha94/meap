<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Http\Requests\Auth\BaseAuthRequest;
use App\Support\ValidationLimits;
use Illuminate\Support\Facades\Auth;
use App\Enums\HttpStatusCode;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RegisterUserRequest extends BaseAuthRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // ログイン状態をチェック
        if (Auth::check()) {
            throw new HttpException(HttpStatusCode::CONFLICT->value, __('auth.general.already_logged_in'));
        }

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
            'name' => ['required', 'string', 'max:' . ValidationLimits::STRING_MAX],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:' . ValidationLimits::STRING_MAX, 'unique:' . User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $name = __('validation.attributes.name');
        $email = __('validation.attributes.email');
        $password = __('validation.attributes.password');

        return [
            'name.required' => __('validation.required', ['attribute' => $name]),
            'name.string' => __('validation.string', ['attribute' => $name]),
            'name.max' => __('validation.max', ['attribute' => $name]),
            'email.required' => __('validation.required', ['attribute' => $email]),
            'email.string' => __('validation.string', ['attribute' => $email]),
            'email.lowercase' => __('validation.lowercase', ['attribute' => $email]),
            'email.email' => __('validation.email', ['attribute' => $email]),
            'email.max' => __('validation.max', ['attribute' => $email, 'string' => 255]),
            'email.unique' => __('auth.register.failed'),
            'password.required' => __('validation.required', ['attribute' => $password]),
            'password.confirmed' => __('validation.password.confirmed', ['attribute' => $password]),
            'password.min' => __('validation.min', ['attribute' => $password, 'string' => 8]),
            'password.letters' => __('validation.password.letters', ['attribute' => $password]),
            'password.mixed' => __('validation.password.mixed', ['attribute' => $password]),
            'password.numbers' => __('validation.password.numbers', ['attribute' => $password]),
            'password.symbols' => __('validation.password.symbols', ['attribute' => $password]),
            'password.uncompromised' => __('validation.password.uncompromised', ['attribute' => $password]),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.auth.register_user');
    }
}
