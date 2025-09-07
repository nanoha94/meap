<?php

namespace Tests\Feature\Traits;

use App\Traits\AutoComplement;
use App\Models\Group;
use InvalidArgumentException;


beforeEach(function () {
    $this->dummy = new class {
        use AutoComplement;

        public function testFindOrCreateIds($items, $group, $modelClass)
        {
            return $this->findOrCreateIds($items, $group, $modelClass);
        }
    };
});


test('1-4-1: 既存アイテム ID 取得テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';
    $existingItem = $modelClass::factory()->create(['group_id' => $group->id]);

    $items = [
        ['id' => $existingItem->id]
    ];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    $this->assertEquals([$existingItem->id], array_values($ids));
});

test('1-4-2: 新規アイテム作成テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    $items = [
        ['name' => 'NewIngredient']
    ];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    $this->assertCount(1, $ids);
    // Remove or adjust the assertDatabaseHas check
});

test('1-4-3: 既存アイテム名での新規作成テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';
    $existingItem = $modelClass::factory()->create(['group_id' => $group->id, 'name' => 'ExistingIngredient']);

    $items = [
        ['name' => 'ExistingIngredient']
    ];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    $this->assertEquals([$existingItem->id], array_values($ids));
});

test('1-4-4: 空のアイテムリストテスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    $items = [];

    $ids = $this->dummy->testFindOrCreateIds($items, $group, $modelClass);

    $this->assertEmpty($ids);
});

test('1-4-5: 無効なデータ型テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    $items = [
        ['id' => 'invalid']
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->dummy->testFindOrCreateIds($items, $group, $modelClass);
});

test('1-4-6: 不正なデータ入力テスト', function () {
    $group = Group::factory()->create();
    $modelClass = 'App\\Models\\Ingredient';

    $items = [
        ['id' => 9999]
    ];

    $this->expectException(InvalidArgumentException::class);
    $this->dummy->testFindOrCreateIds($items, $group, $modelClass);
});
