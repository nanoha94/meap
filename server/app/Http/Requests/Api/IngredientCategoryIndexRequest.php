<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class IngredientCategoryIndexRequest extends BaseApiRequest
{
    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.ingredient_category.index');
    }
}
