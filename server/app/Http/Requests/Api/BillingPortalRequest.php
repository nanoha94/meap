<?php

namespace App\Http\Requests\Api;

class BillingPortalRequest extends BaseApiRequest
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
        return __('operations.billing.portal');
    }
}
