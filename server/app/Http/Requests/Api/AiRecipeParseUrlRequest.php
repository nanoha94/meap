<?php

namespace App\Http\Requests\Api;

use App\Helpers\SafeUrlFetcher;

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
            'url' => ['required', 'url', 'max:2048', 'regex:/^https:\/\//i'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('url')) {
                return;
            }

            $url = $this->input('url');

            if (! is_string($url) || $url === '') {
                return;
            }

            $ssrfError = SafeUrlFetcher::validateUrl($url);

            if ($ssrfError !== null) {
                $validator->errors()->add('url', $ssrfError);
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
            'url.required' => __('validation.required', ['attribute' => 'url']),
            'url.url' => __('validation.url', ['attribute' => 'url']),
            'url.max' => __('validation.max.string', ['attribute' => 'url', 'max' => 2048]),
            'url.regex' => __('validation.url_https_required', ['attribute' => 'url']),
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
