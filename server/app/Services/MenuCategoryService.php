<?php

namespace App\Services;

use App\Models\Group;
use App\Models\MenuCategory;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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

    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'order'];
    }

    protected function getOrderBy(): string | null
    {
        return 'order';
    }

    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, MenuCategory::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'order' => $item->order,
        ];
    }
}
