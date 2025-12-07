<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Auth\BaseAuthRequest;
use Illuminate\Validation\Rules\Password;

class NewPasswordRequest extends BaseAuthRequest
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
            'token' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required'],
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
            'token.required' => __('validation.required', ['attribute' => 'token']),
            'password.required' => __('validation.required', ['attribute' => 'password']),
            'password.confirmed' => __('validation.password.confirmed', ['attribute' => 'password']),
            'password.min' => __('validation.min', ['attribute' => 'password', 'string' => 8]),
            'password.letters' => __('validation.password.letters', ['attribute' => 'password']),
            'password.mixed' => __('validation.password.mixed', ['attribute' => 'password']),
            'password.numbers' => __('validation.password.numbers', ['attribute' => 'password']),
            'password.symbols' => __('validation.password.symbols', ['attribute' => 'password']),
            'password.uncompromised' => __('validation.password.uncompromised', ['attribute' => 'password']),
            'password_confirmation.required' => __('validation.required', ['attribute' => 'password_confirmation'])
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.auth.password_reset');
    }
}
