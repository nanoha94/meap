<?php

namespace App\Services;

use App\Traits\AutoComplement;

class ShoppingService
{
    use AutoComplement;

    /**
     * タグ処理の共通ロジック
     */
    public function processTags($tags, $group, $modelClass): array
    {
        if (empty($tags)) {
            return [];
        }

        $tagIds = $this->findOrCreateIds($tags, $group, $modelClass);
        return empty($tagIds) ? [] : array_values($tagIds);
    }

    /**
     * 買い物アイテムのタグ情報をフォーマット
     */
    public function formatShoppingItemTags($tags): array
    {
        return $tags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name
            ];
        })->toArray();
    }

    /**
     * 買い物アイテムの基本情報をフォーマット
     */
    public function formatShoppingItem($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'isPinned' => (bool)$item->is_pinned,
            'isChecked' => (bool)$item->is_checked,
            'categoryId' => $item->category_id,
            'tags' => $this->formatShoppingItemTags($item->tags),
            'order' => $item->order
        ];
    }

    /**
     * 買い物カテゴリー情報をフォーマット
     */
    public function formatShoppingCategory($category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'isDefault' => (bool)$category->is_default,
            'order' => $category->order
        ];
    }

    /**
     * 買い物アイテムの完全なレスポンスをフォーマット
     */
    public function formatCompleteShoppingItemResponse($item): array
    {
        return [
            'id' => $item->id,
            'categoryId' => $item->category_id,
            'name' => $item->name,
            'isPinned' => $item->is_pinned,
            'isChecked' => $item->is_checked,
            'order' => $item->order,
            'tags' => $this->formatShoppingItemTags($item->tags),
        ];
    }
}
