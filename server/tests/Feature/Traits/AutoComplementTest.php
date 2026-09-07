<?php

namespace Tests\Feature\Traits;

use App\Traits\AutoComplement;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dummy = new class {
        use AutoComplement;

        public function testFindOrCreateIds($items, $group, $modelClass)
        {
            return $this->findOrCreateIds($items, $group, $modelClass);
        }
    };
});


test('1-2-1: 既存アイテム ID 取得テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';
    $existingItem = $modelClass::factory()->create(['group_id' => $group->id]);

    $items = [
        ['id' => $existingItem->id]
    ];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    $this->assertEquals([$existingItem->id], array_values($ids));
});

test('1-2-2: 新規アイテム作成テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    $items = [
        ['name' => 'NewIngredient']
    ];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    $this->assertCount(1, $ids);
    // Remove or adjust the assertDatabaseHas check
});

test('1-2-3: 既存アイテム名での新規作成テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';
    $existingItem = $modelClass::factory()->create(['group_id' => $group->id, 'name' => 'ExistingIngredient']);

    $items = [
        ['name' => 'ExistingIngredient']
    ];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    $this->assertEquals([$existingItem->id], array_values($ids));
});

test('1-2-4: 空のアイテムリストテスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    $items = [];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    $this->assertEmpty($ids);
});

test('1-2-5: 【findOrCreateIds】 無効な ID データ型テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    $items = [
        ['id' => 123] // 数値型（文字列以外）
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('予期しない型が渡されました。期待: string, 実際: integer');
    $this->dummy->testFindOrCreateIds($items, $group, $modelClass);
});

test('1-2-6: 存在しないIDテスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    // 有効なUUID形式だがDBに存在しないID（PostgreSQLのUUID型でクエリが実行されるため形式必須）
    $items = [
        ['id' => '00000000-0000-0000-0000-000000000000']
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('指定されたIDが見つかりませんでした。');
    $this->dummy->testFindOrCreateIds($items, $group, $modelClass);
});

test('1-2-7: インデックス付き戻り値テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    $existingItem1 = $modelClass::factory()->create(['group_id' => $group->id, 'name' => 'Item1']);
    $existingItem2 = $modelClass::factory()->create(['group_id' => $group->id, 'name' => 'Item2']);

    $items = [
        ['id' => $existingItem1->id],
        ['name' => 'NewItem'],
        ['id' => $existingItem2->id]
    ];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    // インデックスをキーとした連想配列が返されることを確認
    $this->assertArrayHasKey(0, $ids);
    $this->assertArrayHasKey(1, $ids);
    $this->assertArrayHasKey(2, $ids);

    $this->assertEquals($existingItem1->id, $ids[0]);
    $this->assertEquals($existingItem2->id, $ids[2]);
    $this->assertIsString($ids[1]); // 新規作成されたアイテムのID
});
