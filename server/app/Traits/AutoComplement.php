<?php

namespace App\Traits;

use App\Models\Group;
use Illuminate\Support\Facades\Log;

trait AutoComplement
{
    /**
     * アイテムのIDを取得し、存在しない場合は作成する
     * @param array|null $items アイテムの配列 [{id: string, name: string}]
     * @param Group $group グループモデル
     * @param string $modelClass 対象モデルのクラス名
     * @return array インデックスをキーとしたIDの連想配列
     */
    protected function findOrCreateIds(
        array $items,
        Group $group,
        string $modelClass
    ): array {

        Log::info('findOrCreateIds', ['items' => $items, 'group' => $group, 'modelClass' => $modelClass]);

        if (empty($items)) {
            return [];
        }

        $ids = [];
        foreach ($items as $idx => $item) {
            if (isset($item['id'])) {
                // 既存アイテムの場合、存在確認
                $existingItem = $modelClass::where('id', $item['id'])
                    ->where('group_id', $group->id)
                    ->first();

                if ($existingItem) {
                    $ids[$idx] = $existingItem->id;
                }
            } else {
                // 新規アイテムの場合、同じ名前のアイテムが存在するか確認
                $existingItem = $modelClass::where('group_id', $group->id)
                    ->where('name', $item['name'])
                    ->first();

                if ($existingItem) {
                    // 既存のアイテムを使用
                    $ids[$idx] = $existingItem->id;
                } else {
                    // 新規アイテムを作成
                    $newItem = $modelClass::create([
                        'group_id' => $group->id,
                        'name' => $item['name']
                    ]);
                    $ids[$idx] = $newItem->id;
                }
            }
        }

        return $ids;
    }
}
