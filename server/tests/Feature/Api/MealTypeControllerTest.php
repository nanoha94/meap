<?php

use App\Models\User;
use App\Models\Group;
use App\Models\GroupUserMapping;
use App\Models\Color;
use App\Services\MealTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Colorマスターデータをシード
    $colors = [
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ];

    foreach ($colors as $color) {
        Color::create($color);
    }

    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $this->group = Group::create([
        'group_size' => 1
    ]);

    GroupUserMapping::create([
        'user_id' => $this->user->id,
        'group_id' => $this->group->id
    ]);

    // ユーザーとグループの関係をリフレッシュ
    $this->user->refresh();
    $this->user->load('group');

    // テスト用の色IDを取得
    $this->yellowColorId = Color::where('name', 'イエロー')->first()->id;
    $this->redColorId = Color::where('name', 'レッド')->first()->id;
    $this->blueColorId = Color::where('name', 'ブルー')->first()->id;
});

// ===== index() メソッドのテストケース =====

test('3-7-1: 【一覧取得】 正常な献立種別一覧取得', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);
    $mealType2Id = $response2->json('data.id');

    $response = $this->actingAs($this->user)->get('/meal-types');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立種別を2件取得しました。',
        'data' => [
            [
                'id' => $mealType1Id,
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ],
            [
                'id' => $mealType2Id,
                'name' => '昼食',
                'colorId' => $this->redColorId,
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
                'colorId',
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-2: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用の献立種別をAPIで作成
    $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);

    $response = $this->actingAs($this->user)->get('/meal-types');

    $response->assertStatus(200);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'colorId',
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-3: 【一覧取得】 order 順での取得確認', function () {
    // 異なるorder順で献立種別をAPIで作成
    $this->actingAs($this->user)->post('/meal-types', [
        'name' => '夕食',
        'colorId' => $this->blueColorId,
        'order' => 2
    ]);
    $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $this->actingAs($this->user)->post('/meal-types', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);

    $response = $this->actingAs($this->user)->get('/meal-types');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // order順で並んでいることを確認
    expect($responseData[0]['name'])->toBe('朝食');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('昼食');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('夕食');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-7-4: 【一覧取得】 空のリスト取得', function () {
    // 献立種別が存在しない状態でテスト
    $response = $this->actingAs($this->user)->get('/meal-types');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立種別を0件取得しました。',
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

test('3-7-5: 【一覧取得】 他グループの献立種別は取得されない', function () {
    // 自グループの献立種別を作成
    $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);

    // 他のグループのユーザーと献立種別をAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    GroupUserMapping::create([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $this->actingAs($otherUser)->post('/meal-types', [
        'name' => '他グループの献立種別',
        'colorId' => $this->redColorId,
        'order' => 0
    ]);

    $response = $this->actingAs($this->user)->get('/meal-types');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 自グループの献立種別のみが取得される
    expect($responseData)->toHaveCount(1);
    expect($responseData[0]['name'])->toBe('朝食');
});

test('3-7-6: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/meal-types');

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

test('3-7-7: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/meal-types');

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

test('3-7-8: 【一覧取得】 データベース接続エラー', function () {
    // MealTypeServiceをモックして例外を発生させる
    $this->mock(MealTypeService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()->andThrow(new \Exception('Database connection error'));
    });

    $response = $this->actingAs($this->user)->get('/meal-types');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立種別の取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ===== store() メソッドのテストケース =====

test('3-7-9: 【新規作成】 正常な献立種別作成', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '献立種別(朝食)を作成しました。',
        'data' => [
            'name' => '朝食',
            'colorId' => $this->yellowColorId,
            'order' => 0
        ]
    ]);

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('meal_types', [
        'group_id' => $this->group->id,
        'name' => '朝食',
        'color_id' => $this->yellowColorId,
        'order' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'colorId',
            'order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-10: 【新規作成】 レスポンス形式確認', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(201);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'colorId',
            'order'
        ]
    ]);

    // データ型の確認
    $data = $response->json('data');
    expect($data['id'])->toBeString();
    expect($data['name'])->toBeString();
    expect($data['colorId'])->toBeString();
    expect($data['order'])->toBeInt();

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-11: 【新規作成】 バリデーションエラー（献立種別名未入力）', function () {
    $data = [
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-12: 【新規作成】 バリデーションエラー（献立種別名が文字列以外）', function () {
    $data = [
        'name' => 123,
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-13: 【新規作成】 バリデーションエラー（献立種別名が 255 文字超過）', function () {
    $data = [
        'name' => str_repeat('a', 256),
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-14: 【新規作成】 バリデーションエラー（色 ID 未入力）', function () {
    $data = [
        'name' => '朝食',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-15: 【新規作成】 バリデーションエラー（色 ID が UUID 形式でない）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => 'invalid-uuid',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-16: 【新規作成】 バリデーションエラー（色 ID が存在しない）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => '00000000-0000-0000-0000-000000000000',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-17: 【新規作成】 バリデーションエラー（order 値が未入力）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-18: 【新規作成】 バリデーションエラー（order 値が数値以外）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 'abc'
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-19: 【新規作成】 バリデーションエラー（order 値が負の値）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => -1
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-20: 【新規作成】 未認証ユーザー', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->post('/meal-types', $data);

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

test('3-7-21: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($user)->post('/meal-types', $data);

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

test('3-7-22: 【新規作成】 データベース接続エラー', function () {
    // MealTypeServiceをモックして例外を発生させる
    $this->mock(MealTypeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立種別の作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-23: 【新規作成】 献立種別作成失敗', function () {
    // MealTypeServiceをモックして例外を発生させる
    $this->mock(MealTypeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()->andThrow(new \Exception('MealType create failed'));
    });

    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-types', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立種別の作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ===== bulkUpdate() メソッドのテストケース =====

test('3-7-24: 【一括更新】 正常な献立種別一括更新', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);
    $mealType2Id = $response2->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $mealType1Id,
                'name' => 'モーニング',
                'colorId' => $this->blueColorId,
                'order' => 1
            ],
            [
                'id' => $mealType2Id,
                'name' => 'ランチ',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立種別を2件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('meal_types', [
        'id' => $mealType1Id,
        'name' => 'モーニング',
        'color_id' => $this->blueColorId,
        'order' => 1
    ]);
    $this->assertDatabaseHas('meal_types', [
        'id' => $mealType2Id,
        'name' => 'ランチ',
        'color_id' => $this->yellowColorId,
        'order' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'colorId',
                'order'
            ]
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-25: 【一括更新】 一括更新成功メッセージの確認', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);
    $mealType2Id = $response2->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $mealType1Id,
                'name' => 'モーニング',
                'colorId' => $this->blueColorId,
                'order' => 1
            ],
            [
                'id' => $mealType2Id,
                'name' => 'ランチ',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('献立種別を2件更新しました。');
});

test('3-7-26: 【一括更新】 一括更新後のデータ取得確認', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $mealType1Id,
                'name' => 'モーニング',
                'colorId' => $this->blueColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(200);

    // 更新後のデータが正しく返されていることを確認
    $responseData = $response->json('data');
    expect($responseData)->toHaveCount(1);
    expect($responseData[0]['id'])->toBe($mealType1Id);
    expect($responseData[0]['name'])->toBe('モーニング');
    expect($responseData[0]['colorId'])->toBe($this->blueColorId);
    expect($responseData[0]['order'])->toBe(0);
});

test('3-7-27: 【一括更新】 バリデーションエラー（data が未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-28: 【一括更新】 バリデーションエラー（data が配列以外）', function () {
    $data = [
        'data' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-29: 【一括更新】 バリデーションエラー（data が空配列）', function () {
    $data = [
        'data' => []
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-30: 【一括更新】 バリデーションエラー（ID 未入力）', function () {
    $data = [
        'data' => [
            [
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-31: 【一括更新】 バリデーションエラー（ID が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => 'invalid-uuid',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-32: 【一括更新】 バリデーションエラー（献立種別名未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-33: 【一括更新】 バリデーションエラー（献立種別名が文字列以外）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => 123,
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-34: 【一括更新】 バリデーションエラー（献立種別名が 255 文字超過）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => str_repeat('a', 256),
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-35: 【一括更新】 バリデーションエラー（色 ID 未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-36: 【一括更新】 バリデーションエラー（色 ID が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => 'invalid-uuid',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-37: 【一括更新】 バリデーションエラー（色 ID が存在しない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => '00000000-0000-0000-0000-000000000000',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-38: 【一括更新】 バリデーションエラー（order 値が未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-39: 【一括更新】 バリデーションエラー（order 値が数値以外）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 'abc'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-40: 【一括更新】 バリデーションエラー（order 値が負の値）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => -1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-41: 【一括更新】 存在しない献立種別の更新', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立種別が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-42: 【一括更新】 他グループの献立種別更新', function () {
    // 他のグループのユーザーと献立種別をAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    GroupUserMapping::create([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $otherResponse = $this->actingAs($otherUser)->post('/meal-types', [
        'name' => '他のグループの献立種別',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $otherMealTypeId = $otherResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $otherMealTypeId,
                'name' => '朝食',
                'colorId' => $this->redColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立種別が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-43: 【一括更新】 未認証ユーザー', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->put('/meal-types/bulk', $data);

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

test('3-7-44: 【一括更新】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($user)->put('/meal-types/bulk', $data);

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

test('3-7-45: 【一括更新】 データベース接続エラー', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    // MealTypeServiceをモックして例外を発生させる
    $this->mock(MealTypeService::class, function ($mock) {
        $mock->shouldReceive('bulkUpdate')
            ->once()->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'data' => [
            [
                'id' => $mealType1Id,
                'name' => 'モーニング',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立種別の一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-46: 【一括更新】 献立種別更新失敗', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    // MealTypeServiceをモックして例外を発生させる
    $this->mock(MealTypeService::class, function ($mock) {
        $mock->shouldReceive('bulkUpdate')
            ->once()->andThrow(new \Exception('MealType update failed'));
    });

    $data = [
        'data' => [
            [
                'id' => $mealType1Id,
                'name' => 'モーニング',
                'colorId' => $this->blueColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-types/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立種別の一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ===== destroy() メソッドのテストケース =====

test('3-7-47: 【削除】 正常な献立種別削除', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    $response = $this->actingAs($this->user)->delete("/meal-types/{$mealType1Id}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立種別(朝食)を削除しました。'
    ]);

    // データベースから削除されていることを確認
    $this->assertDatabaseMissing('meal_types', [
        'id' => $mealType1Id
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-48: 【削除】 削除後の order 整理確認', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);
    $mealType2Id = $response2->json('data.id');

    $response3 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '夕食',
        'colorId' => $this->blueColorId,
        'order' => 2
    ]);
    $mealType3Id = $response3->json('data.id');

    // 中間の献立種別を削除
    $response = $this->actingAs($this->user)->delete("/meal-types/{$mealType2Id}");

    $response->assertStatus(200);

    // 残りの献立種別のorderが整理されていることを確認
    $this->assertDatabaseHas('meal_types', [
        'id' => $mealType1Id,
        'order' => 0
    ]);
    $this->assertDatabaseHas('meal_types', [
        'id' => $mealType3Id,
        'order' => 1
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-49: 【削除】 削除成功メッセージの確認', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    $response = $this->actingAs($this->user)->delete("/meal-types/{$mealType1Id}");

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('献立種別(朝食)を削除しました。');
});

test('3-7-50: 【削除】 存在しない献立種別削除', function () {
    $response = $this->actingAs($this->user)->delete('/meal-types/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立種別が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-51: 【削除】 他グループの献立種別削除', function () {
    // 他のグループのユーザーと献立種別をAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    GroupUserMapping::create([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $otherResponse = $this->actingAs($otherUser)->post('/meal-types', [
        'name' => '他のグループの献立種別',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $otherMealTypeId = $otherResponse->json('data.id');

    $response = $this->actingAs($this->user)->delete("/meal-types/{$otherMealTypeId}");

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立種別が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-52: 【削除】 未認証ユーザー', function () {
    $response = $this->delete('/meal-types/00000000-0000-0000-0000-000000000000');

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

test('3-7-53: 【削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->delete('/meal-types/00000000-0000-0000-0000-000000000000');

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

test('3-7-54: 【削除】 データベース接続エラー', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    // MealTypeServiceをモックして例外を発生させる
    $this->mock(MealTypeService::class, function ($mock) {
        $mock->shouldReceive('delete')
            ->once()->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->delete("/meal-types/{$mealType1Id}");

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立種別の削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-55: 【削除】 献立種別削除失敗', function () {
    // テスト用の献立種別をAPIで作成
    $response1 = $this->actingAs($this->user)->post('/meal-types', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealType1Id = $response1->json('data.id');

    // MealTypeServiceをモックして例外を発生させる
    $this->mock(MealTypeService::class, function ($mock) {
        $mock->shouldReceive('delete')
            ->once()->andThrow(new \Exception('MealType delete failed'));
    });

    $response = $this->actingAs($this->user)->delete("/meal-types/{$mealType1Id}");

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立種別の削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});
