<?php

namespace App\Services;

use App\Models\Group;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategoryService extends AbstractDomainService
{

    protected function getResourceName(): string
    {
        return __('api.attributes.menu_category');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->menuCategories();
    }
}
