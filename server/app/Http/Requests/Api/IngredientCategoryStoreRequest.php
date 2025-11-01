<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class IngredientCategoryStoreRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|max:255|required',
            'order' => 'integer|min:0|required',
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
            'name.string' => __('validation.string', ['attribute' => 'name']),
            'name.max' => __('validation.max.string', ['attribute' => 'name', 'max' => 255]),
            'name.required' => __('validation.required', ['attribute' => 'name']),
            'order.integer' => __('validation.integer', ['attribute' => 'order']),
            'order.min' => __('validation.min.numeric', ['attribute' => 'order', 'min' => 0]),
            'order.required' => __('validation.required', ['attribute' => 'order']),
        ];
    }


    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.ingredient_category.store');
    }
}
