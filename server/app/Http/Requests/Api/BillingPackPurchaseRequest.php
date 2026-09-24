<?php

namespace App\Http\Requests\Api;

use App\Enums\BillingPackType;
use Illuminate\Validation\Rule;

class BillingPackPurchaseRequest extends BaseApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'packType' => $this->route('packType'),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'packType' => ['required', Rule::enum(BillingPackType::class)],
            'cardToken' => ['nullable', 'string'],
        ];
    }

    public function packType(): BillingPackType
    {
        return BillingPackType::from($this->validated('packType'));
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
        return __('operations.billing.purchase_pack');
    }
}
