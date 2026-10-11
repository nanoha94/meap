<?php

namespace App\Http\Requests\Api;

use App\Enums\BillingSubscriptionType;
use Illuminate\Validation\Rule;

class BillingSubscribeRequest extends BaseApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'subscriptionType' => $this->route('subscriptionType'),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscriptionType' => ['required', Rule::enum(BillingSubscriptionType::class)],
            'cardToken' => ['nullable', 'string'],
            'verificationToken' => ['nullable', 'string'],
        ];
    }

    public function subscriptionType(): BillingSubscriptionType
    {
        return BillingSubscriptionType::from($this->validated('subscriptionType'));
    }

    public function cardToken(): ?string
    {
        $cardToken = $this->validated('cardToken');

        if (! is_string($cardToken) || $cardToken === '') {
            return null;
        }

        return $cardToken;
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
        return __('operations.billing.subscribe');
    }
}
