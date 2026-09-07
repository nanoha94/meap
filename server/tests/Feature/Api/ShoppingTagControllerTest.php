<?php

use App\Models\User;
use App\Models\Group;
use App\Models\ShoppingTag;
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

test('3-10-1: 【一覧取得】 正常な買い物タグ一覧取得', function () {
    // テスト用のタグを作成
    ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);
    ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => 'お気に入り'
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物タグを2件取得しました。'
    ]);

    $responseData = $response->json('data');
    expect($responseData)->toHaveCount(2);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-2: 【一覧取得】 タグデータの取得確認', function () {
    // テスト用のタグを作成
    $tag1 = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);
    $tag2 = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => 'お気に入り'
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // タグのIDと名前が正しく取得されることを確認
    expect($responseData[0])->toHaveKey('id');
    expect($responseData[0])->toHaveKey('name');
    expect($responseData[0]['id'])->toBe($tag1->id);
    expect($responseData[0]['name'])->toBe('特売品');
    expect($responseData[1]['id'])->toBe($tag2->id);
    expect($responseData[1]['name'])->toBe('お気に入り');
});

test('3-10-3: 【一覧取得】 タグ総数の確認', function () {
    // テスト用のタグを作成
    ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);
    ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => 'お気に入り'
    ]);
    ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '急ぎ'
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(200);

    // タグの総数が正しく返されることを確認
    $total = $response->json('total');
    expect($total)->toBe(3);
});

test('3-10-4: 【一覧取得】 レスポンス構造確認', function () {
    // テスト用のタグを作成
    ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(200);

    // 正しい構造でレスポンスが返されることを確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name'
            ]
        ],
        'total'
    ]);

    // 各フィールドの型を確認
    $responseData = $response->json('data');
    expect($responseData[0]['id'])->toBeString();
    expect($responseData[0]['name'])->toBeString();
});

test('3-10-5: 【一覧取得】 空のタグリスト', function () {
    // タグを作成しない状態でテスト
    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物タグを0件取得しました。',
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
});

test('3-10-6: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用のタグを作成
    ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(200);

    // 正しいJSON形式でレスポンスが返されることを確認
    $response->assertHeader('Content-Type', 'application/json');

    // JSON構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name'
            ]
        ],
        'total'
    ]);

    // success フィールドが true であることを確認
    expect($response->json('success'))->toBeTrue();
});

test('3-10-7: 【一覧取得】 タグデータの並び順確認', function () {
    // 複数のタグを作成
    $tag1 = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);
    $tag2 = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => 'お気に入り'
    ]);
    $tag3 = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '急ぎ'
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // タグが適切な順序で取得されることを確認（作成順）
    expect($responseData)->toHaveCount(3);
    expect($responseData[0]['id'])->toBe($tag1->id);
    expect($responseData[1]['id'])->toBe($tag2->id);
    expect($responseData[2]['id'])->toBe($tag3->id);
});

test('3-10-8: 【一覧取得】 大量のタグデータ処理', function () {
    // 大量のタグを作成（50個）
    for ($i = 1; $i <= 50; $i++) {
        ShoppingTag::create([
            'group_id' => $this->group->id,
            'name' => "タグ{$i}"
        ]);
    }

    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(200);

    // 全てのタグが正しく取得されることを確認
    $responseData = $response->json('data');
    expect($responseData)->toHaveCount(50);
    expect($response->json('total'))->toBe(50);

    // 最初と最後のタグを確認
    expect($responseData[0]['name'])->toBe('タグ1');
    expect($responseData[49]['name'])->toBe('タグ50');
});

test('3-10-9: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/shopping-tags');

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

test('3-10-10: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/shopping-tags');

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

test('3-10-11: 【一覧取得】 データベース接続エラー', function () {
    $this->mock(\App\Services\ShoppingTagService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->get('/shopping-tags');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});
