<?php

namespace App\Http\Requests\Api;

class AiRecipeParseRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => 'required|file|image|mimes:jpeg,png,webp|max:10240',
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
            'image.required' => __('validation.required', ['attribute' => 'image']),
            'image.file' => __('validation.file', ['attribute' => 'image']),
            'image.image' => __('validation.image', ['attribute' => 'image']),
            'image.mimes' => __('validation.mimes', ['attribute' => 'image', 'values' => 'jpeg,png,webp']),
            'image.max' => __('validation.max.file', ['attribute' => 'image', 'max' => 10240]),
        ];
    }

    /**
     * Get the operation key for error handling.
     */
    protected function getOperationKey(): string
    {
        return __('operations.ai.recipe.parse');
    }
}
