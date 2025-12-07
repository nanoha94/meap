<?php

namespace App\Services;

use App\Models\Group;
use App\Models\ShoppingItem;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShoppingItemService extends AbstractDomainService
{
    private ShoppingTagService $shoppingTagService;
    private ShoppingCategoryService $shoppingCategoryService;

    public function __construct(ShoppingTagService $shoppingTagService, ShoppingCategoryService $shoppingCategoryService)
    {
        $this->shoppingTagService = $shoppingTagService;
        $this->shoppingCategoryService = $shoppingCategoryService;
    }

    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'is_pinned', 'is_checked', 'category_id', 'order'];
    }

    protected function getWithColumns(): array
    {
        return ['category:id,name,is_default,order', 'tags:id,name'];
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.shopping.item');
    }

    protected function getOrderBy(): string | null
    {
        return 'order';
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->shoppingItems();
    }

    protected function getCreateFields(): array
    {
        return ['category_id' => 'categoryId', 'name' => 'name', 'is_pinned' => 'is_pinned', 'is_checked' => 'is_checked', 'order' => 'order'];
    }

    protected function getUpdateFields(): array
    {
        return ['category_id' => 'categoryId', 'name' => 'name', 'is_pinned' => 'isPinned', 'is_checked' => 'isChecked', 'order' => 'order'];
    }

    public function index(Group $group): array
    {
        return DB::transaction(function () use ($group) {
            $query = $this->getGroupRelation($group)
                ->select($this->getSelectColumns());

            if ($this->getWithColumns()) {
                $query->with($this->getWithColumns());
            }

            if ($this->getOrderBy()) {
                $query->orderBy($this->getOrderBy());
            }

            $items = $query->get();

            // 各アイテムにカテゴリーのorderを一時的に追加してソート
            $formattedItems = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'isPinned' => $item->is_pinned,
                    'isChecked' => $item->is_checked,
                    'categoryId' => $item->category_id,
                    'tags' => $item->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name]),
                    'order' => $item->order,
                    // カテゴリーのorderを一時的に追加
                    '_categoryOrder' => $item->category->order,
                ];
            })
                ->sortBy([['_categoryOrder', 'asc'], ['order', 'asc']])
                ->map(function ($item) {
                    // ソート用の一時キーを削除
                    unset($item['_categoryOrder']);
                    return $item;
                })
                ->values()
                ->toArray();

            return $formattedItems;
        });
    }

    protected function formatStoreResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, ShoppingItem::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'isPinned' => $item->is_pinned,
            'isChecked' => $item->is_checked,
            'categoryId' => $item->category_id,
            'tags' => $item->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name
            ]),
            'order' => $item->order
        ];
    }

    protected function formatUpdateResponse(Model $item): array
    {
        // 型チェック
        $this->typeCheck($item, ShoppingItem::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'isPinned' => $item->is_pinned,
            'isChecked' => $item->is_checked,
            'categoryId' => $item->category_id,
            'tags' => $item->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name
            ]),
            'order' => $item->order
        ];
    }

    /**
     * タグ付きでアイテムを作成
     *
     * @param array $data 作成データ（categoryId, name, tags）
     * @param Group $group グループモデル
     * @return array 作成されたアイテムのレスポンスデータ
     * @throws HttpException カテゴリが見つからない場合
     */
    public function create(array $data, Group $group): array
    {
        $data['is_pinned'] = false;
        $data['is_checked'] = false;
        $data['order'] = $group->shoppingItems()->where('category_id', $data['categoryId'])->count() + 1;

        return DB::transaction(function () use ($data, $group) {
            // 1. カテゴリの存在確認とグループIDチェック
            $this->shoppingCategoryService->findItemsByIds([$data['categoryId']], $group)->first();

            // 2. アイテム作成
            $createData = [];
            foreach ($this->getCreateFields() as $field => $dataKey) {
                $createData[$field] = $data[$dataKey];
            }
            $item = $this->getGroupRelation($group)->create($createData);

            // 3. タグの紐づけ
            if (!empty($data['tags'])) {
                $tagIds = $this->shoppingTagService->findOrCreateTagIds($data['tags'], $group);
                if (!empty($tagIds)) {
                    $item->tags()->attach($tagIds);
                }
            }

            // 4. タグとカテゴリを含めて再取得
            $item = $item->fresh(['tags:id,name', 'category:id,name,is_default,order']);

            return $this->formatStoreResponse($item);
        });
    }

    /**
     * 買い物アイテムを一括更新
     *
     * @param array $data 更新データの配列（[['id' => ..., 'categoryId' => ..., 'name' => ..., 'tags' => [...], ...], ...]）
     * @param Group $group グループモデル
     * @return array 更新されたアイテムのレスポンスデータ
     * @throws HttpException アイテムまたはカテゴリが見つからない場合
     */
    public function bulkUpdate(array $data, Group $group): array
    {
        return DB::transaction(function () use ($data, $group) {
            // 1. リクエストされたIDを取得
            $requestedIds = array_column($data, 'id');

            // 2. 一括取得（1回のクエリで効率的にチェック）
            $items = $this->findItemsByIds($requestedIds, $group);

            // 3. カテゴリIDの一括チェック
            $categoryIds = array_unique(array_column($data, 'categoryId'));
            $this->shoppingCategoryService->findItemsByIds($categoryIds, $group);

            // 4. 各アイテムを更新
            $updatedItems = [];
            foreach ($data as $itemData) {
                $item = $items[$itemData['id']];

                // 基本情報の更新
                $updateData = [];
                foreach ($this->getUpdateFields() as $field => $dataKey) {
                    $updateData[$field] = $itemData[$dataKey];
                }
                $item->update($updateData);

                // タグの更新
                if (isset($itemData['tags'])) {
                    $tagIds = $this->shoppingTagService->findOrCreateTagIds($itemData['tags'], $group);
                    $item->tags()->sync($tagIds);
                }

                // タグとカテゴリを含めて再取得
                $item = $item->fresh(['tags:id,name', 'category:id,name,is_default,order']);
                $updatedItems[] = $this->formatUpdateResponse($item);
            }

            return $updatedItems;
        });
    }
}
