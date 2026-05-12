<?php

namespace App\Services;

use App\Models\Group;
use App\Models\RecipeCategory;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipeCategoryService extends AbstractDomainService
{
    protected bool $forgetsMasterCacheOnWrite = true;

    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'order'];
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.recipe_category');
    }

    protected function getOrderBy(): string | null
    {
        return 'order';
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->recipeCategories();
    }

    protected function getCreateFields(): array
    {
        return [
            'name' => 'name',
            'order' => 'order'
        ];
    }

    protected function getUpdateFields(): array
    {
        return [
            'name' => 'name',
            'order' => 'order'
        ];
    }

    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, RecipeCategory::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'order' => $item->order,
        ];
    }
}
