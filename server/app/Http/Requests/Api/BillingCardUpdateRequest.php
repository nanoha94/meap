<?php

namespace App\Http\Requests\Api;

class BillingCardUpdateRequest extends BaseApiRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cardToken' => ['required', 'string'],
            'verificationToken' => ['nullable', 'string'],
        ];
    }

    public function cardToken(): string
    {
        return $this->validated('cardToken');
    }

    public function verificationToken(): ?string
    {
        $verificationToken = $this->validated('verificationToken');

        if (! is_string($verificationToken) || $verificationToken === '') {
            return null;
        }

        return $verificationToken;
    }

    protected function getOperationKey(): string
    {
        return __('operations.billing.card');
    }
}
