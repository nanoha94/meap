<?php

namespace App\Http\Requests\Api;

class AiRecipeParseUrlRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => 'required|url|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => __('validation.required', ['attribute' => 'url']),
            'url.url' => __('validation.url', ['attribute' => 'url']),
            'url.max' => __('validation.max.string', ['attribute' => 'url', 'max' => 2048]),
        ];
    }

    /**
     * Get the operation key for error handling.
     */
    protected function getOperationKey(): string
    {
        return __('operations.ai.recipe.parse_url');
    }
}
