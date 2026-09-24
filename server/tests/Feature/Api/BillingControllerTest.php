<?php

use App\Enums\BillingPackType;
use App\Enums\BillingSubscriptionType;
use App\Models\Color;
use App\Models\Group;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

function sampleBillingStatus(): array
{
    return [
        'plan' => 'free',
        'isSubscribed' => false,
        'subscriptionStatus' => null,
        'subscriptionEndsAt' => null,
        'pendingPlanChange' => null,
        'pmType' => null,
        'pmLastFour' => null,
        'pmExpMonth' => null,
        'pmExpYear' => null,
    ];
}

function billingStatusJsonStructure(): array
{
    return [
        'success',
        'message',
        'data' => [
            'plan',
            'isSubscribed',
            'subscriptionStatus',
            'subscriptionEndsAt',
            'pendingPlanChange',
            'pmType',
            'pmLastFour',
            'pmExpMonth',
            'pmExpYear',
        ],
    ];
}

// ===== status() メソッドのテストケース =====

test('3-15-1: 【課金状態取得】 正常に課金状態を取得できる', function () {
    $status = sampleBillingStatus();

    $this->mock(BillingService::class, function ($mock) use ($status) {
        $mock->shouldReceive('getBillingStatus')
            ->once()
            ->andReturn($status);
    });

    $response = $this->actingAs($this->user)->get('/billing/status');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '課金・サブスクリプション状態を取得しました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure(billingStatusJsonStructure());
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-2: 【課金状態取得】 未認証', function () {
    $response = $this->get('/billing/status');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-3: 【課金状態取得】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->getJson('/billing/status');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-4: 【課金状態取得】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/billing/status');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-5: 【課金状態取得】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('getBillingStatus')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->get('/billing/status');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '課金・サブスクリプション状態の取得に失敗しました。',
    ]);
});

// ===== invoices() メソッドのテストケース =====

function sampleBillingInvoices(): array
{
    return [
        'upcomingInvoice' => [
            'date' => '2024-02-01T00:00:00+00:00',
            'lines' => [
                [
                    'description' => 'スタンダードプラン',
                    'quantity' => 1,
                    'amount' => 580,
                ],
            ],
            'subtotal' => 580,
            'subtotalExcludingTax' => 527,
            'tax' => 53,
            'total' => 580,
            'amountDue' => 580,
        ],
        'pastInvoices' => [
            [
                'id' => 'ch_test_1',
                'date' => '2024-01-01T00:00:00+00:00',
                'description' => 'スタンダードプラン',
                'total' => 580,
            ],
        ],
    ];
}

test('3-15-6: 【請求履歴取得】 正常に請求履歴と次回お支払い予定を取得できる', function () {
    $invoices = sampleBillingInvoices();

    $this->mock(BillingService::class, function ($mock) use ($invoices) {
        $mock->shouldReceive('getInvoices')
            ->once()
            ->andReturn($invoices);
    });

    $response = $this->actingAs($this->user)->get('/billing/invoices');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '請求履歴を取得しました。',
        'data' => $invoices,
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'upcomingInvoice' => [
                'date',
                'lines' => [
                    ['description', 'quantity', 'amount'],
                ],
                'subtotal',
                'subtotalExcludingTax',
                'tax',
                'total',
                'amountDue',
            ],
            'pastInvoices' => [
                ['id', 'date', 'description', 'total'],
            ],
        ],
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-7: 【請求履歴取得】 未認証', function () {
    $response = $this->get('/billing/invoices');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-8: 【請求履歴取得】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->getJson('/billing/invoices');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-9: 【請求履歴取得】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/billing/invoices');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-10: 【請求履歴取得】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('getInvoices')
            ->once()
            ->andThrow(new \Exception('PAY.JP API error'));
    });

    $response = $this->actingAs($this->user)->get('/billing/invoices');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '請求履歴の取得に失敗しました。',
    ]);
});

// ===== subscribe() メソッドのテストケース =====

test('3-15-11: 【サブスク開始】 正常にサブスクリプションを開始できる', function () {
    $status = array_merge(sampleBillingStatus(), [
        'plan' => 'standard',
        'isSubscribed' => true,
        'subscriptionStatus' => 'active',
        'pmType' => 'Visa',
        'pmLastFour' => '4242',
        'pmExpMonth' => 12,
        'pmExpYear' => 2028,
    ]);
    $cardToken = 'tok_test_subscribe';

    $this->mock(BillingService::class, function ($mock) use ($status, $cardToken) {
        $mock->shouldReceive('createSubscription')
            ->once()
            ->with(
                Mockery::type(Group::class),
                Mockery::type(User::class),
                BillingSubscriptionType::STANDARD,
                $cardToken,
            )
            ->andReturn($status);
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/standard', [
        'cardToken' => $cardToken,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'サブスクリプションを開始しました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure(billingStatusJsonStructure());
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-12: 【サブスク開始】 未認証', function () {
    $response = $this->postJson('/billing/subscription/standard', [
        'cardToken' => 'tok_test_subscribe',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-13: 【サブスク開始】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->postJson('/billing/subscription/standard', [
        'cardToken' => 'tok_test_subscribe',
    ]);

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-14: 【サブスク開始】 ルート不一致（subscriptionType 不正）', function () {
    $response = $this->actingAs($this->user)->postJson('/billing/subscription/pro', [
        'cardToken' => 'tok_test_subscribe',
    ]);

    $response->assertStatus(404);
});

test('3-15-15: 【サブスク開始】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->postJson('/billing/subscription/standard', [
        'cardToken' => 'tok_test_subscribe',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-16: 【サブスク開始】 cardToken なしでサブスクリプションを開始できる', function () {
    $status = array_merge(sampleBillingStatus(), [
        'plan' => 'standard',
        'isSubscribed' => true,
        'subscriptionStatus' => 'active',
        'pmType' => 'Visa',
        'pmLastFour' => '4242',
        'pmExpMonth' => 12,
        'pmExpYear' => 2028,
    ]);

    $this->mock(BillingService::class, function ($mock) use ($status) {
        $mock->shouldReceive('createSubscription')
            ->once()
            ->with(
                Mockery::type(Group::class),
                Mockery::type(User::class),
                BillingSubscriptionType::STANDARD,
                null,
            )
            ->andReturn($status);
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/standard');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'サブスクリプションを開始しました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure(billingStatusJsonStructure());
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-17: 【サブスク開始】 バリデーションエラー（cardToken が文字列でない）', function () {
    $response = $this->actingAs($this->user)->postJson('/billing/subscription/standard', [
        'cardToken' => 12345,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cardToken']);
});

test('3-15-18: 【サブスク開始】 既にサブスク済み', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('createSubscription')
            ->once()
            ->andThrow(new HttpException(422, 'すでにサブスクリプションに加入しています。'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/standard', [
        'cardToken' => 'tok_test_subscribe',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'すでにサブスクリプションに加入しています。',
    ]);
});

test('3-15-19: 【サブスク開始】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('createSubscription')
            ->once()
            ->andThrow(new \Exception('PAY.JP API error'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/standard', [
        'cardToken' => 'tok_test_subscribe',
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'サブスクリプションの開始に失敗しました。',
    ]);
});

// ===== cancel() メソッドのテストケース =====

test('3-15-20: 【サブスク解約】 正常にサブスクリプションの解約を受け付けできる', function () {
    $status = array_merge(sampleBillingStatus(), [
        'plan' => 'standard',
        'isSubscribed' => true,
        'subscriptionStatus' => 'canceled',
        'pendingPlanChange' => [
            'nextPlan' => 'free',
            'changesAt' => '2024-03-01T00:00:00+00:00',
        ],
        'pmType' => 'Visa',
        'pmLastFour' => '4242',
        'pmExpMonth' => 12,
        'pmExpYear' => 2028,
    ]);

    $this->mock(BillingService::class, function ($mock) use ($status) {
        $mock->shouldReceive('cancelSubscription')
            ->once()
            ->with(Mockery::type(Group::class))
            ->andReturn($status);
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/cancel');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'サブスクリプションの解約を受け付けました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure(billingStatusJsonStructure());
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-21: 【サブスク解約】 未認証', function () {
    $response = $this->postJson('/billing/subscription/cancel');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-22: 【サブスク解約】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->postJson('/billing/subscription/cancel');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-23: 【サブスク解約】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->postJson('/billing/subscription/cancel');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-24: 【サブスク解約】 有効なサブスクなし', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('cancelSubscription')
            ->once()
            ->andThrow(new HttpException(422, '有効なサブスクリプションがありません。'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/cancel');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => '有効なサブスクリプションがありません。',
    ]);
});

test('3-15-25: 【サブスク解約】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('cancelSubscription')
            ->once()
            ->andThrow(new \Exception('PAY.JP API error'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/cancel');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'サブスクリプションの解約に失敗しました。',
    ]);
});

// ===== resume() メソッドのテストケース =====

test('3-15-26: 【プラン変更予定取り消し】 正常にプラン変更予定を取り消せる', function () {
    $status = array_merge(sampleBillingStatus(), [
        'plan' => 'standard',
        'isSubscribed' => true,
        'subscriptionStatus' => 'active',
    ]);

    $this->mock(BillingService::class, function ($mock) use ($status) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->with(Mockery::type(Group::class));
        $mock->shouldReceive('getBillingStatus')
            ->once()
            ->andReturn($status);
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/resume');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'プラン変更予定を取り消しました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure(billingStatusJsonStructure());
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-27: 【プラン変更予定取り消し】 未認証', function () {
    $response = $this->postJson('/billing/subscription/resume');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-28: 【プラン変更予定取り消し】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->postJson('/billing/subscription/resume');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-29: 【プラン変更予定取り消し】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->postJson('/billing/subscription/resume');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-30: 【プラン変更予定取り消し】 予定変更なし', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->andThrow(new HttpException(422, '取り消すプラン変更予定がありません。'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/resume');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => '取り消すプラン変更予定がありません。',
    ]);
});

test('3-15-31: 【プラン変更予定取り消し】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->andThrow(new \Exception('PAY.JP API error'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/subscription/resume');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'プラン変更予定の取り消しに失敗しました。',
    ]);
});

// ===== purchasePack() メソッドのテストケース =====

test('3-15-32: 【パック購入】 正常にパックを購入できる（light、cardToken あり）', function () {
    $status = array_merge(sampleBillingStatus(), [
        'pmType' => 'Visa',
        'pmLastFour' => '4242',
        'pmExpMonth' => 12,
        'pmExpYear' => 2028,
    ]);
    $cardToken = 'tok_test_pack';

    $this->mock(BillingService::class, function ($mock) use ($status, $cardToken) {
        $mock->shouldReceive('purchasePack')
            ->once()
            ->with(
                Mockery::type(Group::class),
                Mockery::type(User::class),
                BillingPackType::LIGHT,
                $cardToken,
            )
            ->andReturn($status);
    });

    $response = $this->actingAs($this->user)->postJson('/billing/packs/light', [
        'cardToken' => $cardToken,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い切りパックを購入しました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure(billingStatusJsonStructure());
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-33: 【パック購入】 正常にパックを購入できる（value、cardToken なし）', function () {
    $status = array_merge(sampleBillingStatus(), [
        'pmType' => 'Visa',
        'pmLastFour' => '4242',
        'pmExpMonth' => 12,
        'pmExpYear' => 2028,
    ]);

    $this->mock(BillingService::class, function ($mock) use ($status) {
        $mock->shouldReceive('purchasePack')
            ->once()
            ->with(
                Mockery::type(Group::class),
                Mockery::type(User::class),
                BillingPackType::VALUE,
                null,
            )
            ->andReturn($status);
    });

    $response = $this->actingAs($this->user)->postJson('/billing/packs/value');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い切りパックを購入しました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure(billingStatusJsonStructure());
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-34: 【パック購入】 未認証', function () {
    $response = $this->postJson('/billing/packs/light');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-35: 【パック購入】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->postJson('/billing/packs/light');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-36: 【パック購入】 ルート不一致（packType 不正）', function () {
    $response = $this->actingAs($this->user)->postJson('/billing/packs/premium');

    $response->assertStatus(404);
});

test('3-15-37: 【パック購入】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->postJson('/billing/packs/light');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-38: 【パック購入】 バリデーションエラー（cardToken が文字列でない）', function () {
    $response = $this->actingAs($this->user)->postJson('/billing/packs/light', [
        'cardToken' => 12345,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cardToken']);
});

test('3-15-39: 【パック購入】 支払い方法未登録', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('purchasePack')
            ->once()
            ->andThrow(new HttpException(422, '支払い方法が登録されていません。カード情報を登録してください。'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/packs/light');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => '支払い方法が登録されていません。カード情報を登録してください。',
    ]);
});

test('3-15-40: 【パック購入】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('purchasePack')
            ->once()
            ->andThrow(new \Exception('PAY.JP API error'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/packs/light');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い切りパックの購入処理に失敗しました。',
    ]);
});

// ===== updateCard() メソッドのテストケース =====

test('3-15-41: 【カード更新】 正常にカード情報を更新できる', function () {
    $status = array_merge(sampleBillingStatus(), [
        'pmType' => 'Visa',
        'pmLastFour' => '4242',
        'pmExpMonth' => 12,
        'pmExpYear' => 2028,
    ]);
    $cardToken = 'tok_test_card';

    $this->mock(BillingService::class, function ($mock) use ($status, $cardToken) {
        $mock->shouldReceive('updateCard')
            ->once()
            ->with(
                Mockery::type(Group::class),
                Mockery::type(User::class),
                $cardToken,
            )
            ->andReturn($status);
    });

    $response = $this->actingAs($this->user)->postJson('/billing/card', [
        'cardToken' => $cardToken,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'カード情報を更新しました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure(billingStatusJsonStructure());
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-42: 【カード更新】 未認証', function () {
    $response = $this->postJson('/billing/card', [
        'cardToken' => 'tok_test_card',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-43: 【カード更新】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->postJson('/billing/card', [
        'cardToken' => 'tok_test_card',
    ]);

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-44: 【カード更新】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->postJson('/billing/card', [
        'cardToken' => 'tok_test_card',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-45: 【カード更新】 バリデーションエラー（cardToken 未入力）', function () {
    $response = $this->actingAs($this->user)->postJson('/billing/card', [
        'cardToken' => '',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cardToken']);
});

test('3-15-46: 【カード更新】 バリデーションエラー（cardToken が文字列でない）', function () {
    $response = $this->actingAs($this->user)->postJson('/billing/card', [
        'cardToken' => 12345,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cardToken']);
});

test('3-15-47: 【カード更新】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('updateCard')
            ->once()
            ->andThrow(new \Exception('PAY.JP API error'));
    });

    $response = $this->actingAs($this->user)->postJson('/billing/card', [
        'cardToken' => 'tok_test_card',
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'カード情報の更新に失敗しました。',
    ]);
});

// ===== deleteCard() メソッドのテストケース =====

test('3-15-48: 【カード削除】 正常にカード情報を削除できる', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('deleteCard')
            ->once()
            ->with(Mockery::type(Group::class));
    });

    $response = $this->actingAs($this->user)->deleteJson('/billing/card');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'カード情報を削除しました。',
        'data' => null,
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-49: 【カード削除】 未認証', function () {
    $response = $this->deleteJson('/billing/card');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-50: 【カード削除】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->deleteJson('/billing/card');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-51: 【カード削除】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->deleteJson('/billing/card');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-52: 【カード削除】 課金アカウント未登録', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('deleteCard')
            ->once()
            ->andThrow(new HttpException(422, '課金情報が登録されていません。'));
    });

    $response = $this->actingAs($this->user)->deleteJson('/billing/card');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => '課金情報が登録されていません。',
    ]);
});

test('3-15-53: 【カード削除】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('deleteCard')
            ->once()
            ->andThrow(new \Exception('PAY.JP API error'));
    });

    $response = $this->actingAs($this->user)->deleteJson('/billing/card');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'カード情報の削除に失敗しました。',
    ]);
});

