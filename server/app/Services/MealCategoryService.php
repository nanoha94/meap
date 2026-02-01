<?php

namespace App\Services;

use App\Models\Group;
use App\Models\MealCategory;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealCategoryService extends AbstractDomainService
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

    protected function getWithColumns(): array
    {
        return ['color'];
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.meal_category');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->mealCategories();
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
        $this->typeCheck($item, MealCategory::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'colorCodeHex' => $item->color->color_code_hex,
            'order' => $item->order,
        ];
    }

    protected function formatStoreResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealCategory::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'colorCodeHex' => $item->color->color_code_hex,
            'order' => $item->order,
        ];
    }

    protected function formatUpdateResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, MealCategory::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'colorCodeHex' => $item->color->color_code_hex,
            'order' => $item->order,
        ];
    }
}
