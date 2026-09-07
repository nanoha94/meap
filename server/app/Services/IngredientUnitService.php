<?php

namespace App\Services;

use App\Models\Group;
use App\Models\IngredientUnit;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredientUnitService extends AbstractDomainService
{
    protected bool $forgetsMasterCacheOnWrite = true;

    protected function getResourceName(): string
    {
        return __('api.attributes.ingredient_unit');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->ingredientUnits();
    }

    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'position', 'requires_quantity', 'order'];
    }

    protected function getOrderBy(): string | null
    {
        return 'order';
    }

    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, IngredientUnit::class);
        return [
            'id' => $item->id,
            'name' => $item->name,
            'position' => $item->position,
            'requiresQuantity' => $item->requires_quantity,
            'order' => $item->order,
        ];
    }
}
