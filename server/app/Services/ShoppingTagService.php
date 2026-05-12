<?php

namespace App\Services;

use App\Models\Group;
use App\Models\ShoppingTag;
use App\Services\AbstractDomainService;
use App\Traits\AutoComplement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoppingTagService extends AbstractDomainService
{
    use AutoComplement;

    protected bool $forgetsMasterCacheOnWrite = true;

    protected function getSelectColumns(): array
    {
        return ['id', 'name'];
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.shopping.tag');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->shoppingTags();
    }


    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, ShoppingTag::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
        ];
    }

    /**
     * タグ名からタグIDを検索、または新規作成
     *
     * @param array $tags タグの配列（[['name' => 'タグ名'], ...]形式）
     * @param Group $group グループモデル
     * @return array タグIDの配列
     */
    public function findOrCreateTagIds(array $tags, Group $group): array
    {
        if (empty($tags)) {
            return [];
        }

        $tagIds = $this->findOrCreateIds($tags, $group, ShoppingTag::class);
        if ($this->autoComplementCreatedInLastFindOrCreate) {
            MasterService::forgetGroupCache($group);
        }

        return empty($tagIds) ? [] : array_values($tagIds);
    }
}
