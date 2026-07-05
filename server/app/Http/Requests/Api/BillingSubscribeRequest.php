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
        ];
    }

    public function subscriptionType(): BillingSubscriptionType
    {
        return BillingSubscriptionType::from($this->validated('subscriptionType'));
    }

    protected function getOperationKey(): string
    {
        return __('operations.billing.subscribe');
    }
}
