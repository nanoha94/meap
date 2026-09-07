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
        ];
    }

    public function packType(): BillingPackType
    {
        return BillingPackType::from($this->validated('packType'));
    }

    protected function getOperationKey(): string
    {
        return __('operations.billing.purchase_pack');
    }
}
