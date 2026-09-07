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
    $response->assertJsonStructure([
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
    ]);
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
                'id' => 'in_test_1',
                'date' => '2024-01-01T00:00:00+00:00',
                'total' => 580,
                'invoiceUrl' => 'https://invoice.stripe.com/i/test',
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
                'tax',
                'total',
                'amountDue',
            ],
            'pastInvoices' => [
                ['id', 'date', 'total', 'invoiceUrl'],
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
            ->andThrow(new \Exception('Stripe API error'));
    });

    $response = $this->actingAs($this->user)->get('/billing/invoices');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '請求履歴の取得に失敗しました。',
    ]);
});

// ===== subscribe() メソッドのテストケース =====

test('3-15-11: 【サブスク開始】 Checkout URL を返却する', function () {
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_sub';

    $this->mock(BillingService::class, function ($mock) use ($checkoutUrl) {
        $mock->shouldReceive('createSubscriptionCheckout')
            ->once()
            ->with(
                Mockery::type(Group::class),
                Mockery::type(User::class),
                BillingSubscriptionType::STANDARD,
            )
            ->andReturn($checkoutUrl);
    });

    $response = $this->actingAs($this->user)->post('/billing/subscribe/standard');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Stripe Checkout セッションを作成しました。',
        'data' => [
            'checkoutUrl' => $checkoutUrl,
        ],
    ]);
});

test('3-15-12: 【サブスク開始】 未認証', function () {
    $response = $this->post('/billing/subscribe/standard');

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

    $response = $this->actingAs($user)->postJson('/billing/subscribe/standard');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-14: 【サブスク開始】 ルート不一致（subscriptionType 不正）', function () {
    $response = $this->actingAs($this->user)->post('/billing/subscribe/pro');

    $response->assertStatus(404);
});

test('3-15-15: 【サブスク開始】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post('/billing/subscribe/standard');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-16: 【サブスク開始】 既にサブスク済み', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('createSubscriptionCheckout')
            ->once()
            ->andThrow(new HttpException(422, 'すでにサブスクリプションに加入しています。'));
    });

    $response = $this->actingAs($this->user)->post('/billing/subscribe/standard');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'すでにサブスクリプションに加入しています。',
    ]);
});

test('3-15-17: 【サブスク開始】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('createSubscriptionCheckout')
            ->once()
            ->andThrow(new \Exception('Stripe API error'));
    });

    $response = $this->actingAs($this->user)->post('/billing/subscribe/standard');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'サブスクリプションの開始に失敗しました。',
    ]);
});

// ===== portal() メソッドのテストケース =====

test('3-15-18: 【Customer Portal】 Portal URL を返却する', function () {
    $portalUrl = 'https://billing.stripe.com/p/session/test_portal';

    $this->mock(BillingService::class, function ($mock) use ($portalUrl) {
        $mock->shouldReceive('createPortalSession')
            ->once()
            ->with(Mockery::type(Group::class))
            ->andReturn($portalUrl);
    });

    $response = $this->actingAs($this->user)->post('/billing/portal');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Stripe Customer Portal セッションを作成しました。',
        'data' => [
            'portalUrl' => $portalUrl,
        ],
    ]);
});

test('3-15-19: 【Customer Portal】 未認証', function () {
    $response = $this->post('/billing/portal');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-20: 【Customer Portal】 メール未認証', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->postJson('/billing/portal');

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
});

test('3-15-21: 【Customer Portal】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post('/billing/portal');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-22: 【Customer Portal】 課金アカウント未登録', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('createPortalSession')
            ->once()
            ->andThrow(new HttpException(422, '課金情報が登録されていません。先にサブスクリプションまたは買い切りパックを購入してください。'));
    });

    $response = $this->actingAs($this->user)->post('/billing/portal');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => '課金情報が登録されていません。先にサブスクリプションまたは買い切りパックを購入してください。',
    ]);
});

test('3-15-23: 【Customer Portal】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('createPortalSession')
            ->once()
            ->andThrow(new \Exception('Stripe API error'));
    });

    $response = $this->actingAs($this->user)->post('/billing/portal');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'Customer Portal セッションの作成に失敗しました。',
    ]);
});

// ===== resume() メソッドのテストケース =====

test('3-15-24: 【プラン変更予定取り消し】 正常にプラン変更予定を取り消せる', function () {
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

    $response = $this->actingAs($this->user)->post('/billing/subscription/resume');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'プラン変更予定を取り消しました。',
        'data' => $status,
    ]);
    $response->assertJsonStructure([
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
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-15-25: 【プラン変更予定取り消し】 未認証', function () {
    $response = $this->post('/billing/subscription/resume');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-26: 【プラン変更予定取り消し】 メール未認証', function () {
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

test('3-15-27: 【プラン変更予定取り消し】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post('/billing/subscription/resume');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-28: 【プラン変更予定取り消し】 予定変更なし', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->andThrow(new HttpException(422, '取り消すプラン変更予定がありません。'));
    });

    $response = $this->actingAs($this->user)->post('/billing/subscription/resume');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => '取り消すプラン変更予定がありません。',
    ]);
});

test('3-15-29: 【プラン変更予定取り消し】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->andThrow(new \Exception('Stripe API error'));
    });

    $response = $this->actingAs($this->user)->post('/billing/subscription/resume');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'プラン変更予定の取り消しに失敗しました。',
    ]);
});

// ===== purchasePack() メソッドのテストケース =====

test('3-15-30: 【パック購入】 Checkout URL を返却する（light）', function () {
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_light';

    $this->mock(BillingService::class, function ($mock) use ($checkoutUrl) {
        $mock->shouldReceive('createPackCheckout')
            ->once()
            ->with(
                Mockery::type(Group::class),
                Mockery::type(User::class),
                BillingPackType::LIGHT,
            )
            ->andReturn($checkoutUrl);
    });

    $response = $this->actingAs($this->user)->post('/billing/packs/light');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Stripe Checkout セッションを作成しました。',
        'data' => [
            'checkoutUrl' => $checkoutUrl,
        ],
    ]);
});

test('3-15-31: 【パック購入】 Checkout URL を返却する（value）', function () {
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_value';

    $this->mock(BillingService::class, function ($mock) use ($checkoutUrl) {
        $mock->shouldReceive('createPackCheckout')
            ->once()
            ->with(
                Mockery::type(Group::class),
                Mockery::type(User::class),
                BillingPackType::VALUE,
            )
            ->andReturn($checkoutUrl);
    });

    $response = $this->actingAs($this->user)->post('/billing/packs/value');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Stripe Checkout セッションを作成しました。',
        'data' => [
            'checkoutUrl' => $checkoutUrl,
        ],
    ]);
});

test('3-15-32: 【パック購入】 未認証', function () {
    $response = $this->post('/billing/packs/light');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-15-33: 【パック購入】 メール未認証', function () {
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

test('3-15-34: 【パック購入】 ルート不一致（packType 不正）', function () {
    $response = $this->actingAs($this->user)->post('/billing/packs/premium');

    $response->assertStatus(404);
});

test('3-15-35: 【パック購入】 グループに所属していない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post('/billing/packs/light');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
});

test('3-15-36: 【パック購入】 サービス例外', function () {
    $this->mock(BillingService::class, function ($mock) {
        $mock->shouldReceive('createPackCheckout')
            ->once()
            ->andThrow(new \Exception('Stripe API error'));
    });

    $response = $this->actingAs($this->user)->post('/billing/packs/light');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い切りパックの購入処理に失敗しました。',
    ]);
});
