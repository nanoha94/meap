<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\Group;
use App\Models\ShoppingCategory;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShoppingCategoryService extends AbstractDomainService
{
    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'is_default', 'order'];
    }

    protected function getOrderBy(): string | null
    {
        return 'order';
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.shopping.category');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->shoppingCategories();
    }


    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, ShoppingCategory::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'isDefault' => $item->is_default,
            'order' => $item->order,
        ];
    }

    protected function getCreateFields(): array
    {
        return ['name' => 'name', 'is_default' => 'is_default', 'order' => 'order'];
    }


    protected function getUpdateFields(): array
    {
        return ['name' => 'name', 'order' => 'order'];
    }

    protected function formatStoreResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, ShoppingCategory::class);

        return ['id' => $item->id, 'name' => $item->name, 'isDefault' => $item->is_default, 'order' => $item->order];
    }


    protected function formatUpdateResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, ShoppingCategory::class);

        return ['id' => $item->id, 'name' => $item->name, 'isDefault' => $item->is_default, 'order' => $item->order];
    }

    /**
     * 削除前の検証:デフォルトカテゴリは削除不可
     *
     * @param Collection $items 削除対象のアイテムコレクション
     * @throws HttpException デフォルトカテゴリが含まれている場合
     */
    protected function validateBeforeDelete(Collection $items): void
    {
        // 型チェック
        $this->typeCheck($items, Collection::class);
        $this->typeCheckCollection($items, ShoppingCategory::class);

        if ($items) {
            $defaultCategory = $items->where('is_default', true)->first();
            if ($defaultCategory) {
                throw new HttpException(
                    HttpStatusCode::BAD_REQUEST->value,
                    __('api.cannot_delete', ['name' => $defaultCategory->name, 'attribute' => $this->getResourceName()])
                );
            }
        }
    }

    /**
     * カテゴリを作成（is_defaultは常にfalse）
     */
    public function create(array $data, Group $group): array
    {
        // is_defaultを固定値として追加
        $data['is_default'] = false;

        return parent::create($data, $group);
    }
}
