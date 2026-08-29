<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;
use App\Support\ValidationLimits;
use Carbon\Carbon;

class MealPlanIndexRequest extends BaseApiRequest
{
    /**
     * GETクエリの "true"/"false" 文字列を boolean に変換する
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('include_ingredients')) {
            $value = $this->input('include_ingredients');
            $normalized = match (strtolower((string) $value)) {
                'true' => true,
                'false' => false,
                default => $value,
            };
            $this->merge(['include_ingredients' => $normalized]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => 'required|date|date_format:Y-m-d',
            'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
            'include_ingredients' => 'sometimes|boolean',
        ];
    }

    /**
     * Configure the validator (date_from〜date_to の期間上限チェック).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->hasAny(['date_from', 'date_to'])) {
                return;
            }

            $dateFrom = $this->input('date_from');
            $dateTo = $this->input('date_to');

            if (! is_string($dateFrom) || ! is_string($dateTo)) {
                return;
            }

            $from = Carbon::createFromFormat('Y-m-d', $dateFrom);
            $to = Carbon::createFromFormat('Y-m-d', $dateTo);

            if ($from === false || $to === false) {
                return;
            }

            $inclusiveDays = $from->startOfDay()->diffInDays($to->startOfDay()) + 1;

            if ($inclusiveDays > ValidationLimits::MEAL_PLAN_INDEX_DATE_RANGE_MAX_DAYS) {
                $validator->errors()->add(
                    'date_to',
                    __('validation.date_range_max_days', [
                        'max' => ValidationLimits::MEAL_PLAN_INDEX_DATE_RANGE_MAX_DAYS,
                    ])
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_from.required' => __('validation.required', ['attribute' => 'date_from']),
            'date_from.date' => __('validation.date', ['attribute' => 'date_from']),
            'date_from.date_format' => __('validation.date_format', ['attribute' => 'date_from', 'format' => 'Y-m-d']),
            'date_to.required' => __('validation.required', ['attribute' => 'date_to']),
            'date_to.date' => __('validation.date', ['attribute' => 'date_to']),
            'date_to.date_format' => __('validation.date_format', ['attribute' => 'date_to', 'format' => 'Y-m-d']),
            'date_to.after_or_equal' => __('validation.after_or_equal', ['attribute' => 'date_to', 'date' => 'date_from']),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.meal_plan.index');
    }
}
