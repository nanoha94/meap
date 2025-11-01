<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class RecipeIndexRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'integer|min:1|nullable',
            'per_page' => 'integer|min:1|max:100|nullable',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'page.integer' => __('validation.integer', ['attribute' => 'page']),
            'page.min' => __('validation.min.numeric', ['attribute' => 'page', 'min' => 1]),
            'per_page.integer' => __('validation.integer', ['attribute' => 'per_page']),
            'per_page.min' => __('validation.min.numeric', ['attribute' => 'per_page', 'min' => 1]),
            'per_page.max' => __('validation.max.numeric', ['attribute' => 'per_page', 'max' => 100]),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.recipe.index');
    }
}
