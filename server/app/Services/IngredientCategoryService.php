<?php

namespace App\Services;

use App\Models\Group;
use App\Models\IngredientCategory;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredientCategoryService extends AbstractDomainService
{
    protected function getUpdateFields(): array
    {
        return [
            'name' => 'name',
            'order' => 'order'
        ];
    }

    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'order'];
    }

    protected function getOrderBy(): string | null
    {
        return 'order';
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.ingredient_category');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->ingredientCategories();
    }

    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, IngredientCategory::class);
        return ['id' => $item->id, 'name' => $item->name, 'order' => $item->order];
    }

    protected function getCreateFields(): array
    {
        return ['name' => 'name', 'order' => 'order'];
    }

    protected function formatStoreResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, IngredientCategory::class);
        return ['id' => $item->id, 'name' => $item->name, 'order' => $item->order];
    }

    protected function formatUpdateResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, IngredientCategory::class);
        return ['id' => $item->id, 'name' => $item->name, 'order' => $item->order];
    }
}
