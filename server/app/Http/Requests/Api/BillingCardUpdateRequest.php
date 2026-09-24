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
        ];
    }

    public function cardToken(): string
    {
        return $this->validated('cardToken');
    }

    protected function getOperationKey(): string
    {
        return __('operations.billing.card');
    }
}
