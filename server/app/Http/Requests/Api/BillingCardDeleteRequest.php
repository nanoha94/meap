<?php

namespace App\Http\Requests\Api;

class BillingCardDeleteRequest extends BaseApiRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    protected function getOperationKey(): string
    {
        return __('operations.billing.card_delete');
    }
}
