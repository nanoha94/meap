<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class ImageBulkUploadRequest extends BaseApiRequest
{
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // images配列からnull要素を除外
        if ($this->has('images') && is_array($this->images)) {
            $this->merge([
                'images' => array_filter($this->images, fn($image) => $image !== null),
            ]);
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
            'images' => 'array|min:1|max:20|required',
            'images.*' => 'file|image|mimes:jpeg,png,gif,webp|max:10240|required',
            'upload_path' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === null) {
                        return;
                    }
                    if (str_contains($value, '..')) {
                        $fail(__('validation.custom.upload_path.no_traversal'));
                    }
                    if (str_starts_with($value, '/') || str_starts_with($value, '\\')) {
                        $fail(__('validation.custom.upload_path.no_absolute'));
                    }
                },
            ],
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
            'images.array' => __('validation.array', ['attribute' => 'images']),
            'images.min' => __('validation.min.array', ['attribute' => 'images', 'min' => 1]),
            'images.max' => __('validation.max.array', ['attribute' => 'images', 'max' => 20]),
            'images.required' => __('validation.required', ['attribute' => 'images']),
            'images.*.file' => __('validation.file', ['attribute' => 'images.*']),
            'images.*.image' => __('validation.image', ['attribute' => 'images.*']),
            'images.*.mimes' => __('validation.mimes', ['attribute' => 'images.*', 'values' => 'jpeg,png,gif,webp']),
            'images.*.max' => __('validation.max.file', ['attribute' => 'images.*', 'max' => 10240]),
            'images.*.required' => __('validation.required', ['attribute' => 'images.*']),
            'upload_path.string' => __('validation.string', ['attribute' => 'upload_path']),
            'upload_path.max' => __('validation.max.string', ['attribute' => 'upload_path', 'max' => 255]),
        ];
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.image.bulk_upload');
    }
}
