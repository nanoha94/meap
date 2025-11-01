<?php

namespace App\Services;

use App\Models\Group;
use App\Models\MealType;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealTypeService extends AbstractDomainService
{
    protected function getUpdateFields(): array
    {
        return [
            'name' => 'name',
            'color_id' => 'colorId',
            'order' => 'order'
        ];
    }

    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'color_id', 'order'];
    }

    protected function getOrderBy(): string | null
    {
        return 'order';
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.meal_type');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->mealTypes();
    }

    protected function getCreateFields(): array
    {
        return [
            'name' => 'name',
            'color_id' => 'colorId',
            'order' => 'order'
        ];
    }

    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealType::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'colorId' => $item->color_id,
            'order' => $item->order,
        ];
    }

    protected function formatStoreResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealType::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'colorId' => $item->color_id,
            'order' => $item->order,
        ];
    }

    protected function formatUpdateResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealType::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'colorId' => $item->color_id,
            'order' => $item->order,
        ];
    }
}
