<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class RecipeUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:2048',
            'thumbnailId' => 'nullable|string',
            'categoryIds' => 'nullable|array',
            'categoryIds.*' => 'required|string',
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'nullable|string',
            'ingredients.*.name' => 'required|string',
            'ingredients.*.unitId' => 'required|string',
            'ingredients.*.categoryId' => 'required|string',
            'ingredients.*.quantity' => 'nullable|numeric',
            'ingredients.*.order' => 'nullable|integer',
            'steps' => 'nullable|array',
            'steps.*.id' => 'nullable|string',
            'steps.*.instruction' => 'required|string',
            'steps.*.imageId' => 'nullable|string',
            'steps.*.order' => 'required|integer',
            'memo' => 'nullable|string',
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
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.recipe.name')]),
            'name.string' => __('validation.string', ['attribute' => __('validation.attributes.recipe.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.recipe.name'), 'max' => 255]),
            'url.string' => __('validation.string', ['attribute' => __('validation.attributes.url')]),
            'url.max' => __('validation.max.string', ['attribute' => __('validation.attributes.url'), 'max' => 2048]),
            'categoryIds.array' => __('validation.array', ['attribute' => __('validation.attributes.recipe_category.name')]),
            'categoryIds.*.required' => __('validation.required', ['attribute' => __('validation.attributes.recipe_category.name')]),
            'categoryIds.*.string' => __('validation.string', ['attribute' => __('validation.attributes.recipe_category.name')]),
            'ingredients.array' => __('validation.array', ['attribute' => __('validation.attributes.ingredient.name')]),
            'ingredients.*.id.string' => __('validation.string', ['attribute' => __('validation.attributes.ingredient.id')]),
            'ingredients.*.name.required' => __('validation.required', ['attribute' => __('validation.attributes.ingredient.name')]),
            'ingredients.*.name.string' => __('validation.string', ['attribute' => __('validation.attributes.ingredient.name')]),
            'ingredients.*.unitId.required' => __('validation.required', ['attribute' => __('validation.attributes.ingredient.unit_id')]),
            'ingredients.*.unitId.string' => __('validation.string', ['attribute' => __('validation.attributes.ingredient.unit_id')]),
            'ingredients.*.categoryId.required' => __('validation.required', ['attribute' => __('validation.attributes.ingredient.category_id')]),
            'ingredients.*.categoryId.string' => __('validation.string', ['attribute' => __('validation.attributes.ingredient.category_id')]),
            'ingredients.*.quantity.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.ingredient.quantity')]),
            'ingredients.*.order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.ingredient.order')]),
            'steps.array' => __('validation.array', ['attribute' => __('validation.attributes.recipe.step')]),
            'steps.*.id.string' => __('validation.string', ['attribute' => __('validation.attributes.recipe.step_id')]),
            'steps.*.instruction.required' => __('validation.required', ['attribute' => __('validation.attributes.instruction')]),
            'steps.*.instruction.string' => __('validation.string', ['attribute' => __('validation.attributes.instruction')]),
            'steps.*.imageId.string' => __('validation.string', ['attribute' => __('validation.attributes.image_id')]),
            'steps.*.order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.order')]),
            'memo.string' => __('validation.string', ['attribute' => __('validation.attributes.memo')]),
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator  $validator
     * @return void
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        // バリデーション失敗時のログ記録とレスポンス生成
        $errorMessages = $validator->errors()->all();
        $primaryMessage = !empty($errorMessages) ? $errorMessages[0] : __('api.general.validation_error');

        // ValidationExceptionを作成
        $validationException = ValidationException::withMessages($validator->errors()->toArray());

        // ExceptionHandlerTraitを使用してレスポンスを生成
        $response = $this->handleException(
            $validationException,
            $this,
            $primaryMessage,
            __('operations.auth.password_reset')
        );

        // HttpResponseExceptionでレスポンスを投げる
        throw new HttpResponseException($response);
    }
}
