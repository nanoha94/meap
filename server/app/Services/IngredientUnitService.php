<?php

namespace App\Services;

use App\Models\Group;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredientUnitService extends AbstractDomainService
{

    protected function getResourceName(): string
    {
        return __('api.attributes.ingredient_unit');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->ingredientUnits();
    }
}
