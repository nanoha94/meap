<?php

use App\Data\ParsedRecipe;
use App\Enums\GroupPlan;
use App\Interfaces\AiRecipeParserInterface;
use App\Models\Color;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

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

    $this->parsedRecipe = ParsedRecipe::fromArray([
        'name' => 'テストレシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'quantity' => 1,
                'quantityDisplay' => null,
                'unitName' => '個',
                'categoryName' => '野菜',
            ],
        ],
        'steps' => [
            ['instruction' => '玉ねぎを切る'],
        ],
    ]);
});

function postAiRecipeParse($test, User $user, ?UploadedFile $file = null, bool $withoutImage = false): \Illuminate\Testing\TestResponse
{
    $payload = $withoutImage ? [] : ['image' => $file ?? UploadedFile::fake()->image('recipe.jpg', 100, 100)];

    return $test->actingAs($user)->post('/ai/recipes/parse-img', $payload);
}

// ===== parseImage() メソッドのテストケース =====

test('3-13-1: 【AIレシピ画像解析】 正常に画像を解析できる', function () {
    $this->mock(AiRecipeParserInterface::class, function ($mock) {
        $mock->shouldReceive('parseImage')
            ->once()
            ->andReturn($this->parsedRecipe);
    });

    $response = postAiRecipeParse($this, $this->user);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像からレシピ情報を読み取りました。',
        'data' => [
            'name' => 'テストレシピ',
            'servingCount' => 2,
            'ingredients' => [
                [
                    'name' => '玉ねぎ',
                    'quantity' => 1,
                    'quantityDisplay' => '1',
                    'unitName' => '個',
                    'categoryName' => '野菜',
                ],
            ],
            'steps' => [
                ['instruction' => '玉ねぎを切る'],
            ],
        ],
    ]);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(2);
});

test('3-13-2: 【AIレシピ画像解析】 quantityDisplay がレスポンスに含まれる', function () {
    $parsedRecipeWithDisplay = ParsedRecipe::fromArray([
        'name' => '分数レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 0.5,
                'quantityDisplay' => '1/2',
                'unitName' => '大さじ',
                'categoryName' => '調味料',
            ],
            [
                'name' => '玉ねぎ',
                'quantity' => 1.5,
                'quantityDisplay' => '1と1/2',
                'unitName' => '個',
                'categoryName' => '野菜',
            ],
        ],
        'steps' => [
            ['instruction' => '材料を混ぜる'],
        ],
    ]);

    $this->mock(AiRecipeParserInterface::class, function ($mock) use ($parsedRecipeWithDisplay) {
        $mock->shouldReceive('parseImage')
            ->once()
            ->andReturn($parsedRecipeWithDisplay);
    });

    $response = postAiRecipeParse($this, $this->user);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'ingredients' => [
                [
                    'name' => '塩',
                    'quantity' => 0.5,
                    'quantityDisplay' => '1/2',
                    'unitName' => '大さじ',
                    'categoryName' => '調味料',
                ],
                [
                    'name' => '玉ねぎ',
                    'quantity' => 1.5,
                    'quantityDisplay' => '1と1/2',
                    'unitName' => '個',
                    'categoryName' => '野菜',
                ],
            ],
        ],
    ]);
});

test('3-13-3: 【AIレシピ画像解析】 適量の材料は quantity / quantityDisplay が両方 null', function () {
    $parsedRecipeWithTekiryo = ParsedRecipe::fromArray([
        'name' => '調味料レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 1,
                'quantityDisplay' => '1',
                'unitName' => '適量',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [
            ['instruction' => '塩を加える'],
        ],
    ]);

    $this->mock(AiRecipeParserInterface::class, function ($mock) use ($parsedRecipeWithTekiryo) {
        $mock->shouldReceive('parseImage')
            ->once()
            ->andReturn($parsedRecipeWithTekiryo);
    });

    $response = postAiRecipeParse($this, $this->user);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'ingredients' => [
                [
                    'name' => '塩',
                    'quantity' => null,
                    'quantityDisplay' => null,
                    'unitName' => '適量',
                    'categoryName' => '調味料',
                ],
            ],
        ],
    ]);
});

test('3-13-4: 【AIレシピ画像解析】 quantity のみの分数は quantityDisplay が補完される', function () {
    $parsedRecipeWithFraction = ParsedRecipe::fromArray([
        'name' => '分数レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 0.5,
                'quantityDisplay' => null,
                'unitName' => '大さじ',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [
            ['instruction' => '塩を加える'],
        ],
    ]);

    $this->mock(AiRecipeParserInterface::class, function ($mock) use ($parsedRecipeWithFraction) {
        $mock->shouldReceive('parseImage')
            ->once()
            ->andReturn($parsedRecipeWithFraction);
    });

    $response = postAiRecipeParse($this, $this->user);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'ingredients' => [
                [
                    'name' => '塩',
                    'quantity' => 0.5,
                    'quantityDisplay' => '1/2',
                    'unitName' => '大さじ',
                    'categoryName' => '調味料',
                ],
            ],
        ],
    ]);
});

test('3-13-5: 【AIレシピ画像解析】 quantity と display が矛盾する場合は display を優先する', function () {
    $parsedRecipeWithConflict = ParsedRecipe::fromArray([
        'name' => '矛盾レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 1,
                'quantityDisplay' => '1/2',
                'unitName' => '大さじ',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [
            ['instruction' => '塩を加える'],
        ],
    ]);

    $this->mock(AiRecipeParserInterface::class, function ($mock) use ($parsedRecipeWithConflict) {
        $mock->shouldReceive('parseImage')
            ->once()
            ->andReturn($parsedRecipeWithConflict);
    });

    $response = postAiRecipeParse($this, $this->user);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'ingredients' => [
                [
                    'name' => '塩',
                    'quantity' => 0.5,
                    'quantityDisplay' => '1/2',
                    'unitName' => '大さじ',
                    'categoryName' => '調味料',
                ],
            ],
        ],
    ]);
});

test('3-13-6: 【AIレシピ画像解析】 未認証', function () {
    $response = $this->post('/ai/recipes/parse-img', [
        'image' => UploadedFile::fake()->image('recipe.jpg'),
    ]);

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);
});

test('3-13-7: 【AIレシピ画像解析】 バリデーションエラー（image 未指定）', function () {
    $response = postAiRecipeParse($this, $this->user, withoutImage: true);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['image']);

    $responseData = $response->json();
    expect($responseData['errors']['image'])->toContain('imageは必ず指定してください。');
});

test('3-13-8: 【AIレシピ画像解析】 バリデーションエラー（image が画像ファイルでない）', function () {
    $file = UploadedFile::fake()->create('recipe.pdf', 100, 'application/pdf');

    $response = postAiRecipeParse($this, $this->user, $file);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['image']);

    $responseData = $response->json();
    expect($responseData['errors']['image'])->toContain('imageには画像ファイルを指定してください。');
});

test('3-13-9: 【AIレシピ画像解析】 バリデーションエラー（image の MIME 形式不正）', function () {
    $file = UploadedFile::fake()->create('recipe.gif', 100, 'image/gif');

    $response = postAiRecipeParse($this, $this->user, $file);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['image']);

    $responseData = $response->json();
    expect($responseData['errors']['image'])->toContain('imageにはjpeg,png,webpタイプのファイルを指定してください。');
});

test('3-13-10: 【AIレシピ画像解析】 バリデーションエラー（image のファイルサイズ超過）', function () {
    $file = UploadedFile::fake()->create('large.jpg', 11000);

    $response = postAiRecipeParse($this, $this->user, $file);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['image']);

    $responseData = $response->json();
    expect($responseData['errors']['image'])->toContain('imageには、10240 kb以下のファイルを指定してください。');
});

test('3-13-11: 【AIレシピ画像解析】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = postAiRecipeParse($this, $user);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-13-12: 【AIレシピ画像解析】 月次利用上限超過', function () {
    $this->group->update([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
        'ai_pack_remaining' => 0,
        'ai_usage_reset_at' => now()->addMonth(),
    ]);

    $response = postAiRecipeParse($this, $this->user);

    $response->assertStatus(429);
    $response->assertJson([
        'success' => false,
        'message' => '今月のAI利用回数の上限に達しました。',
        'error_type' => 'ai_monthly_limit_exceeded',
    ]);
});

test('3-13-13: 【AIレシピ画像解析】 AI 解析失敗時に利用回数が返却される', function () {
    $this->mock(AiRecipeParserInterface::class, function ($mock) {
        $mock->shouldReceive('parseImage')
            ->once()
            ->andThrow(new \Exception('unexpected'));
    });

    $response = postAiRecipeParse($this, $this->user);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '画像からのレシピ読み取りに失敗しました。',
    ]);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(3);
});

test('3-13-14: 【AIレシピ画像解析】 短時間の連続リクエストでレート制限', function () {
    config(['ai.rate_limit_per_minute' => 2]);

    $this->mock(AiRecipeParserInterface::class, function ($mock) {
        $mock->shouldReceive('parseImage')
            ->twice()
            ->andReturn($this->parsedRecipe);
    });

    postAiRecipeParse($this, $this->user)->assertStatus(200);
    postAiRecipeParse($this, $this->user)->assertStatus(200);

    $response = postAiRecipeParse($this, $this->user);

    $response->assertStatus(429);
    $response->assertJson([
        'success' => false,
        'message' => '短時間で複数回リクエストしているので機能を一時停止しています。時間をおいて試してください。',
        'error_type' => 'ai_rate_limit_exceeded',
    ]);
});

// ===== parseUrl() メソッドのテストケース =====

function postAiRecipeParseUrl($test, User $user, array $payload = ['url' => 'https://example.com/recipe/123']): \Illuminate\Testing\TestResponse
{
    return $test->actingAs($user)->postJson('/ai/recipes/parse-url', $payload);
}

test('3-13-15: 【AIレシピURL解析】 正常にURLからレシピを解析できる', function () {
    $this->mock(AiRecipeParserInterface::class, function ($mock) {
        $mock->shouldReceive('parseUrl')
            ->once()
            ->andReturn($this->parsedRecipe);
    });

    $response = postAiRecipeParseUrl($this, $this->user);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'URLからレシピ情報を読み取りました。',
        'data' => [
            'name' => 'テストレシピ',
            'servingCount' => 2,
            'ingredients' => [
                [
                    'name' => '玉ねぎ',
                    'quantity' => 1,
                    'quantityDisplay' => '1',
                    'unitName' => '個',
                    'categoryName' => '野菜',
                ],
            ],
            'steps' => [
                ['instruction' => '玉ねぎを切る'],
            ],
        ],
    ]);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(2);
});

test('3-13-16: 【AIレシピURL解析】 未認証', function () {
    $response = $this->postJson('/ai/recipes/parse-url', [
        'url' => 'https://example.com/recipe/123',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);
});

test('3-13-17: 【AIレシピURL解析】 バリデーションエラー（url 未指定）', function () {
    $response = postAiRecipeParseUrl($this, $this->user, []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);

    $responseData = $response->json();
    expect($responseData['errors']['url'])->toContain('urlは必ず指定してください。');
});

test('3-13-18: 【AIレシピURL解析】 バリデーションエラー（url が URL 形式でない）', function () {
    $response = postAiRecipeParseUrl($this, $this->user, ['url' => 'not-a-valid-url']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);

    $responseData = $response->json();
    expect($responseData['errors']['url'])->toContain('urlに正しい形式のURLを指定してください。');
});

test('3-13-19: 【AIレシピURL解析】 バリデーションエラー（url が 2048 文字を超える）', function () {
    $response = postAiRecipeParseUrl($this, $this->user, [
        'url' => 'https://example.com/' . str_repeat('a', 2040),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);

    $responseData = $response->json();
    expect($responseData['errors']['url'])->toContain('urlは、2048文字以内で指定してください。');
});

test('3-13-20: 【AIレシピURL解析】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = postAiRecipeParseUrl($this, $user);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-13-21: 【AIレシピURL解析】 月次利用上限超過', function () {
    $this->group->update([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
        'ai_pack_remaining' => 0,
        'ai_usage_reset_at' => now()->addMonth(),
    ]);

    $response = postAiRecipeParseUrl($this, $this->user);

    $response->assertStatus(429);
    $response->assertJson([
        'success' => false,
        'message' => '今月のAI利用回数の上限に達しました。',
        'error_type' => 'ai_monthly_limit_exceeded',
    ]);
});

test('3-13-22: 【AIレシピURL解析】 AI 解析失敗時に利用回数が返却される', function () {
    $this->mock(AiRecipeParserInterface::class, function ($mock) {
        $mock->shouldReceive('parseUrl')
            ->once()
            ->andThrow(new \Exception('unexpected'));
    });

    $response = postAiRecipeParseUrl($this, $this->user);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'URLからのレシピ読み取りに失敗しました。',
    ]);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(3);
});

test('3-13-23: 【AIレシピURL解析】 短時間の連続リクエストでレート制限', function () {
    config(['ai.rate_limit_per_minute' => 2]);

    $this->mock(AiRecipeParserInterface::class, function ($mock) {
        $mock->shouldReceive('parseUrl')
            ->twice()
            ->andReturn($this->parsedRecipe);
    });

    postAiRecipeParseUrl($this, $this->user)->assertStatus(200);
    postAiRecipeParseUrl($this, $this->user)->assertStatus(200);

    $response = postAiRecipeParseUrl($this, $this->user);

    $response->assertStatus(429);
    $response->assertJson([
        'success' => false,
        'message' => '短時間で複数回リクエストしているので機能を一時停止しています。時間をおいて試してください。',
        'error_type' => 'ai_rate_limit_exceeded',
    ]);
});

