<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class MealCategoryDestroyRequest extends BaseApiRequest
{
    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.meal_category.destroy');
    }
}
