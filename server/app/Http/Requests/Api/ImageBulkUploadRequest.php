<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class ImageBulkUploadRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'images' => 'array|min:1|max:20|required',
            'images.*' => 'file|image|mimes:jpeg,png,jpg,gif,webp|max:10240|required',
            'directory' => 'string|max:255|nullable',
        ];

        return $rules;
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
            'images.*.mimes' => __('validation.mimes', ['attribute' => 'images.*', 'values' => 'png,jpeg,jpg,gif,webp']),
            'images.*.max' => __('validation.max.file', ['attribute' => 'images.*', 'max' => 10240]),
            'images.*.required' => __('validation.required', ['attribute' => 'images.*']),
            'directory.string' => __('validation.string', ['attribute' => 'directory']),
            'directory.max' => __('validation.max.string', ['attribute' => 'directory', 'max' => 255]),
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
