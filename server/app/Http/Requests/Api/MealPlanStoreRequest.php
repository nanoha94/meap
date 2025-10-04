<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class MealPlanStoreRequest extends FormRequest
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
            'date' => 'required|date',
            'mealTypeId' => 'required|uuid',
            'menu' => 'required|array|min:1',
            'menu.*.recipeIds' => 'required|array|min:1',
            'menu.*.recipeIds.*' => 'required|uuid',
            'menu.*.courseTypeId' => 'required|uuid',
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
            'date.required' => __('validation.required', ['attribute' => __('validation.attributes.meal_plan.date')]),
            'date.date' => __('validation.date', ['attribute' => __('validation.attributes.meal_plan.date')]),
            'mealTypeId.required' => __('validation.required', ['attribute' => __('validation.attributes.meal_plan.meal_type_id')]),
            'mealTypeId.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.meal_plan.meal_type_id')]),
            'menu.required' => __('validation.required', ['attribute' => __('validation.attributes.menu')]),
            'menu.array' => __('validation.array', ['attribute' => __('validation.attributes.menu')]),
            'menu.min' => __('validation.min.array', ['attribute' => __('validation.attributes.menu'), 'min' => 1]),
            'menu.*.recipeIds.required' => __('validation.required', ['attribute' => __('validation.attributes.recipe_id')]),
            'menu.*.recipeIds.array' => __('validation.array', ['attribute' => __('validation.attributes.recipe_id')]),
            'menu.*.recipeIds.min' => __('validation.min.array', ['attribute' => __('validation.attributes.recipe_id'), 'min' => 1]),
            'menu.*.recipeIds.*.required' => __('validation.required', ['attribute' => __('validation.attributes.recipe_id')]),
            'menu.*.recipeIds.*.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.recipe_id')]),
            'menu.*.courseTypeId.required' => __('validation.required', ['attribute' => __('validation.attributes.course_type_id')]),
            'menu.*.courseTypeId.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.course_type_id')]),
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
