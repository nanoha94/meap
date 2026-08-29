<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;
use App\Support\ValidationLimits;

class ShoppingItemBulkStoreRequest extends BaseApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => 'array|min:1|max:' . ValidationLimits::BULK_ITEM_DATA_MAX . '|required',
            'data.*.name' => 'string|max:255|required',
            'data.*.categoryId' => 'uuid|required',
            'data.*.order' => 'integer|min:0|required',
            'data.*.isPinned' => 'boolean|required',
            'data.*.isChecked' => 'boolean|required',
            'data.*.tags' => 'array|nullable|max:' . ValidationLimits::SHOPPING_ITEM_TAGS_MAX,
            'data.*.tags.*.id' => 'uuid|nullable',
            'data.*.tags.*.name' => 'string|max:255|nullable',
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
            'data.array' => __('validation.array', ['attribute' => 'data']),
            'data.min' => __('validation.min.array', ['attribute' => 'data', 'min' => 1]),
            'data.max' => __('validation.max.array', ['attribute' => 'data', 'max' => ValidationLimits::BULK_ITEM_DATA_MAX]),
            'data.required' => __('validation.required', ['attribute' => 'data']),
            'data.*.name.string' => __('validation.string', ['attribute' => 'data.*.name']),
            'data.*.name.max' => __('validation.max.string', ['attribute' => 'data.*.name', 'max' => 255]),
            'data.*.name.required' => __('validation.required', ['attribute' => 'data.*.name']),
            'data.*.categoryId.uuid' => __('validation.uuid', ['attribute' => 'data.*.categoryId']),
            'data.*.categoryId.required' => __('validation.required', ['attribute' => 'data.*.categoryId']),
            'data.*.isPinned.boolean' => __('validation.boolean', ['attribute' => 'data.*.isPinned']),
            'data.*.isPinned.required' => __('validation.required', ['attribute' => 'data.*.isPinned']),
            'data.*.isChecked.boolean' => __('validation.boolean', ['attribute' => 'data.*.isChecked']),
            'data.*.isChecked.required' => __('validation.required', ['attribute' => 'data.*.isChecked']),
            'data.*.order.integer' => __('validation.integer', ['attribute' => 'data.*.order']),
            'data.*.order.min' => __('validation.min.numeric', ['attribute' => 'data.*.order', 'min' => 0]),
            'data.*.order.required' => __('validation.required', ['attribute' => 'data.*.order']),
            'data.*.tags.array' => __('validation.array', ['attribute' => 'data.*.tags']),
            'data.*.tags.max' => __('validation.max.array', ['attribute' => 'data.*.tags', 'max' => ValidationLimits::SHOPPING_ITEM_TAGS_MAX]),
            'data.*.tags.*.id.uuid' => __('validation.uuid', ['attribute' => 'data.*.tags.*.id']),
            'data.*.tags.*.name.string' => __('validation.string', ['attribute' => 'data.*.tags.*.name']),
            'data.*.tags.*.name.max' => __('validation.max.string', ['attribute' => 'data.*.tags.*.name', 'max' => 255]),
            'data.*.tags.*.name.required' => __('validation.required', ['attribute' => 'data.*.tags.*.name']),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->input('data', []);
            if (! is_array($data)) {
                return;
            }

            foreach ($data as $dataIndex => $item) {
                $tags = $item['tags'] ?? [];
                if (! is_array($tags)) {
                    continue;
                }

                foreach ($tags as $tagIndex => $tag) {
                    $hasId = ! empty($tag['id']);
                    $hasName = isset($tag['name']) && is_string($tag['name']) && $tag['name'] !== '';
                    if (! $hasId && ! $hasName) {
                        $field = "data.{$dataIndex}.tags.{$tagIndex}.name";
                        $validator->errors()->add(
                            $field,
                            __('validation.id_or_name_required', [
                                'attribute' => __('validation.attributes.shopping.item.tag_name'),
                                'id' => 'id',
                                'name' => 'name',
                            ])
                        );
                    }
                }
            }
        });
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.shopping_item.bulk_store');
    }
}
