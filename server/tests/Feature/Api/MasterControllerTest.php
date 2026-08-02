<?php

use App\Models\Color;
use App\Models\Group;
use App\Models\User;
use App\Services\MasterService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ] as $color) {
        Color::create($color);
    }

    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->group = Group::createGroup();
    $this->group->users()->attach($this->user->id);
    $this->user->refresh();
    $this->user->load('groups');
});

// ===== __invoke() メソッドのテストケース =====

test('3-16-1: 【マスターデータ取得】 正常にマスターデータを取得できる', function () {
    $response = $this->actingAs($this->user)->get('/master');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'マスターデータを取得しました。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'users',
            'recipeCategories',
            'ingredientUnits',
            'mealCategories',
            'shoppingCategories',
            'shoppingTags',
        ],
    ]);
    $response->assertHeader('Content-Type', 'application/json');

    expect($response->json('data.users'))->toHaveCount(1);
    expect($response->json('data.users.0.id'))->toBe($this->user->id);
});

test('3-16-2: 【マスターデータ取得】 レスポンスに全 6 種のキーが含まれる', function () {
    $response = $this->actingAs($this->user)->get('/master');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'users',
            'recipeCategories',
            'ingredientUnits',
            'mealCategories',
            'shoppingCategories',
            'shoppingTags',
        ],
    ]);

    $data = $response->json('data');
    expect($data)->toHaveKeys([
        'users',
        'recipeCategories',
        'ingredientUnits',
        'mealCategories',
        'shoppingCategories',
        'shoppingTags',
    ]);
    expect($data['ingredientUnits'])->not->toBeEmpty();
    expect($data['mealCategories'])->not->toBeEmpty();
    expect($data['shoppingCategories'])->not->toBeEmpty();
});

test('3-16-3: 【マスターデータ取得】 未認証', function () {
    $response = $this->get('/master');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-16-4: 【マスターデータ取得】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-16-5: 【マスターデータ取得】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-16-6: 【マスターデータ取得】 サービス例外', function () {
    $this->mock(MasterService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->get('/master');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'マスターデータの取得に失敗しました。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});
