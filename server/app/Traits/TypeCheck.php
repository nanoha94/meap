<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;
use LogicException;

trait TypeCheck
{
    /**
     * 型チェックを行う
     * 
     * @param mixed $item チェック対象
     * @param string $expected 期待するクラス名
     * @throws LogicException 型が一致しない場合
     */
    protected function typeCheck(mixed $item, string $expected): void
    {
        if (!($item instanceof $expected)) {
            throw new LogicException(__('api.general.unexpected_type', [
                'expected' => $expected,
                'actual' => get_class($item)
            ]));
        }
    }

    /**
     * Collectionの中身の型チェックを行う
     * 
     * @param Collection $collection チェック対象のCollection
     * @param string $expectedItemType 期待する要素のクラス名
     * @throws LogicException 型が一致しない場合
     */
    protected function typeCheckCollection(Collection $collection, string $expectedItemType): void
    {
        if ($collection->isEmpty()) {
            return;
        }

        $firstItem = $collection->first();
        if (!($firstItem instanceof $expectedItemType)) {
            throw new LogicException(__('api.general.unexpected_collection_type', [
                'expected' => "Collection<{$expectedItemType}>",
                'actual' => 'Collection<' . get_class($firstItem) . '>'
            ]));
        }
    }
}
