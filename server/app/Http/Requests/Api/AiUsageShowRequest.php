<?php

namespace App\Http\Requests\Api;

class AiUsageShowRequest extends BaseApiRequest
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
        return __('operations.ai.usage.show');
    }
}
