<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;

class RecipeDestroyRequest extends BaseApiRequest
{
    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.recipe.destroy');
    }
}
