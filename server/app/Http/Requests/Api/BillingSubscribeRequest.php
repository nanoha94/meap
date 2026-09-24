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

    protected function getOperationKey(): string
    {
        return __('operations.billing.subscribe');
    }
}
