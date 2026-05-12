<?php

use App\Models\User;
use App\Models\Group;
use App\Models\IngredientUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $this->group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $this->user->id,
        'group_id' => $this->group->id
    ]);

    // ユーザーとグループの関係をリフレッシュ
    $this->user->refresh();
    $this->user->load('groups');
});

// ===== index() メソッドのテストケース =====

test('3-12-1: 【一覧取得】 正常な食材単位一覧取得', function () {
    // テスト用の単位を作成
    $unit1 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 0,
        'is_default' => false
    ]);

    $unit2 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'ml',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 1,
        'is_default' => false
    ]);

    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '食材単位を2件取得しました。',
        'data' => [
            [
                'id' => $unit1->id,
                'name' => 'g',
                'position' => 'suffix',
                'requiresQuantity' => true,
                'order' => 0
            ],
            [
                'id' => $unit2->id,
                'name' => 'ml',
                'position' => 'suffix',
                'requiresQuantity' => true,
                'order' => 1
            ]
        ],
        'total' => 2
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'position',
                'requiresQuantity',
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-12-2: 【一覧取得】 単位情報の並び順確認', function () {
    // 異なるorder順で単位を作成
    $unit1 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 2,
        'is_default' => false
    ]);

    $unit2 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'ml',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 0,
        'is_default' => false
    ]);

    $unit3 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '個',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 1,
        'is_default' => false
    ]);

    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // order順で並んでいることを確認
    expect($responseData[0]['name'])->toBe('ml');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('個');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('g');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-12-3: 【一覧取得】 空の単位一覧', function () {
    // 単位が存在しない状態でテスト
    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '食材単位を0件取得しました。',
        'data' => [],
        'total' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data',
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-12-4: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用の単位を作成
    IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 0,
        'is_default' => false
    ]);

    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(200);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'position',
                'requiresQuantity',
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-12-5: 【一覧取得】 各フィールドの確認', function () {
    // テスト用の単位を作成
    $unit = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 0,
        'is_default' => false
    ]);

    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 各フィールドが正しく返されることを確認
    expect($responseData[0])->toHaveKey('id');
    expect($responseData[0])->toHaveKey('name');
    expect($responseData[0])->toHaveKey('position');
    expect($responseData[0])->toHaveKey('requiresQuantity');
    expect($responseData[0])->toHaveKey('order');

    expect($responseData[0]['id'])->toBe($unit->id);
    expect($responseData[0]['name'])->toBe('g');
    expect($responseData[0]['position'])->toBe('suffix');
    expect($responseData[0]['requiresQuantity'])->toBe(true);
    expect($responseData[0]['order'])->toBe(0);
});

test('3-12-6: 【一覧取得】 position フィールドの確認', function () {
    // prefix と suffix の両方の単位を作成
    $unit1 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '大さじ',
        'position' => 'prefix',
        'requires_quantity' => true,
        'order' => 0,
        'is_default' => false
    ]);

    $unit2 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 1,
        'is_default' => false
    ]);

    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // position が 'prefix' または 'suffix' で返されることを確認
    expect($responseData[0]['position'])->toBeIn(['prefix', 'suffix']);
    expect($responseData[1]['position'])->toBeIn(['prefix', 'suffix']);

    expect($responseData[0]['position'])->toBe('prefix');
    expect($responseData[1]['position'])->toBe('suffix');
});

test('3-12-7: 【一覧取得】 requiresQuantity フィールドの確認', function () {
    // requires_quantity が true と false の両方の単位を作成
    $unit1 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 0,
        'is_default' => false
    ]);

    $unit2 = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'prefix',
        'requires_quantity' => false,
        'order' => 1,
        'is_default' => false
    ]);

    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // requiresQuantity が boolean で返されることを確認
    expect($responseData[0])->toHaveKey('requiresQuantity');
    expect($responseData[0]['requiresQuantity'])->toBeBool();
    expect($responseData[1]['requiresQuantity'])->toBeBool();

    expect($responseData[0]['requiresQuantity'])->toBe(true);
    expect($responseData[1]['requiresQuantity'])->toBe(false);
});

test('3-12-8: 【一覧取得】 他グループの単位は取得されない', function () {
    // 自グループの単位を作成
    $ownUnit = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 0,
        'is_default' => false
    ]);

    // 他のグループのユーザーとグループを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    // 他グループの単位を作成
    $otherUnit = IngredientUnit::create([
        'group_id' => $otherGroup->id,
        'name' => 'ml',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 0,
        'is_default' => false
    ]);

    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 自グループの単位のみが取得されることを確認
    expect(count($responseData))->toBe(1);
    expect($responseData[0]['id'])->toBe($ownUnit->id);
    expect($responseData[0]['id'])->not->toBe($otherUnit->id);
});

test('3-12-9: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/ingredient-units');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-12-10: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/ingredient-units');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-12-11: 【一覧取得】 データベース接続エラー', function () {
    $this->mock(\App\Services\IngredientUnitService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->get('/ingredient-units');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});
