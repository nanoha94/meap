<?php

use App\Enums\BillingPackType;
use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Models\Group;
use App\Models\User;
use App\Services\BillingService;
use App\Services\BillingWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Invoice;
use Laravel\Cashier\SubscriptionBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'billing.price_ids.subscription_standard' => 'price_test_standard',
        'billing.price_ids.pack_light' => 'price_test_light',
        'billing.price_ids.pack_value' => 'price_test_value',
        'billing.subscription_type' => 'default',
        'app.frontend_url' => 'https://app.test.example',
    ]);

    $this->service = app(BillingService::class);
    $this->user = User::factory()->create();
});

function makeCheckout(string $url = 'https://checkout.stripe.com/c/pay/cs_test'): Checkout
{
    $session = \Stripe\Checkout\Session::constructFrom([
        'id' => 'cs_test',
        'url' => $url,
    ]);

    return new Checkout(null, $session);
}

function createBillingGroup(array $attributes = []): Group
{
    return Group::factory()->create(array_merge([
        'plan' => GroupPlan::FREE,
        'stripe_id' => 'cus_test_123',
    ], $attributes));
}

function createSubscriptionRecord(Group $group, array $attributes = []): void
{
    $group->subscriptions()->create(array_merge([
        'type' => config('billing.subscription_type'),
        'stripe_id' => 'sub_test_' . str()->random(8),
        'stripe_status' => 'active',
        'stripe_price' => config('billing.price_ids.subscription_standard'),
        'ends_at' => null,
    ], $attributes));
}

function frontendCheckoutSessionOptions(): array
{
    $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

    return [
        'success_url' => $frontendUrl . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $frontendUrl . '/settings/billing?checkout=canceled',
        'automatic_tax' => ['enabled' => true],
        'customer_update' => ['address' => 'auto'],
    ];
}

function mockGroupForSubscriptionCheckout(Group $group, string $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_sub'): Group
{
    $checkout = makeCheckout($checkoutUrl);

    $builder = Mockery::mock(SubscriptionBuilder::class);
    $builder->shouldReceive('withMetadata')
        ->once()
        ->with(['group_id' => $group->id])
        ->andReturnSelf();
    $builder->shouldReceive('checkout')
        ->once()
        ->with(frontendCheckoutSessionOptions())
        ->andReturn($checkout);

    /** @var Group&\Mockery\MockInterface $mock */
    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('subscribed')
        ->with(config('billing.subscription_type'))
        ->andReturn(false);
    $mock->shouldReceive('newSubscription')
        ->with(config('billing.subscription_type'), config('billing.price_ids.subscription_standard'))
        ->once()
        ->andReturn($builder);
    $mock->shouldReceive('createOrGetStripeCustomer')
        ->once()
        ->andReturnUsing(function () use ($group) {
            return \Stripe\Customer::constructFrom(['id' => $group->stripe_id ?? 'cus_test']);
        });
    $mock->shouldReceive('refresh')->once();

    return $mock;
}

function mockGroupForPackCheckout(
    Group $group,
    BillingPackType $packType,
    string $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_pack',
): Group {
    $checkout = makeCheckout($checkoutUrl);
    $priceId = config('billing.price_ids.' . $packType->configKey());

    /** @var Group&\Mockery\MockInterface $mock */
    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('checkout')
        ->once()
        ->with(
            [$priceId => 1],
            Mockery::on(function (array $sessionOptions) use ($group, $packType) {
                $checkoutOptions = frontendCheckoutSessionOptions();

                return ($sessionOptions['success_url'] ?? null) === $checkoutOptions['success_url']
                    && ($sessionOptions['cancel_url'] ?? null) === $checkoutOptions['cancel_url']
                    && ($sessionOptions['automatic_tax'] ?? null) === $checkoutOptions['automatic_tax']
                    && ($sessionOptions['customer_update'] ?? null) === $checkoutOptions['customer_update']
                    && ($sessionOptions['metadata']['type'] ?? null) === 'pack'
                    && ($sessionOptions['metadata']['group_id'] ?? null) === $group->id
                    && ($sessionOptions['metadata']['credits'] ?? null) === (string) $packType->credits();
            }),
        )
        ->andReturn($checkout);
    $mock->shouldReceive('createOrGetStripeCustomer')
        ->once()
        ->andReturnUsing(function () use ($group) {
            return \Stripe\Customer::constructFrom(['id' => $group->stripe_id ?? 'cus_test']);
        });
    $mock->shouldReceive('refresh')->once();

    return $mock;
}

// ===== createSubscriptionCheckout() メソッドのテストケース =====

test('4-3-1: 【サブスク Checkout】 Checkout URL を返す', function () {
    $group = createBillingGroup();
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_sub';
    $group = mockGroupForSubscriptionCheckout($group, $checkoutUrl);

    $url = $this->service->createSubscriptionCheckout(
        $group,
        $this->user,
        BillingSubscriptionType::STANDARD,
    );

    expect($url)->toBe($checkoutUrl);
});

test('4-3-2: 【サブスク Checkout】 メタデータに group_id がセットされる', function () {
    $group = createBillingGroup();
    $capturedMetadata = null;

    $checkout = makeCheckout();
    $builder = Mockery::mock(SubscriptionBuilder::class);
    $builder->shouldReceive('withMetadata')
        ->once()
        ->withArgs(function (array $metadata) use (&$capturedMetadata) {
            $capturedMetadata = $metadata;

            return true;
        })
        ->andReturnSelf();
    $builder->shouldReceive('checkout')->once()->andReturn($checkout);

    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('subscribed')->andReturn(false);
    $mock->shouldReceive('newSubscription')->once()->andReturn($builder);
    $mock->shouldReceive('createOrGetStripeCustomer')->once();
    $mock->shouldReceive('refresh')->once();

    $this->service->createSubscriptionCheckout(
        $mock,
        $this->user,
        BillingSubscriptionType::STANDARD,
    );

    expect($capturedMetadata)->toBe(['group_id' => $group->id]);
});

test('4-3-3: 【サブスク Checkout】 既にサブスク済みなら 422 を投げる', function () {
    $group = createBillingGroup();
    createSubscriptionRecord($group);

    expect(fn() => $this->service->createSubscriptionCheckout(
        $group->fresh(),
        $this->user,
        BillingSubscriptionType::STANDARD,
    ))->toThrow(HttpException::class, 'すでにサブスクリプションに加入しています。');
});

test('4-3-4: 【サブスク Checkout】 価格 ID 未設定なら 500 を投げる', function () {
    config(['billing.price_ids.subscription_standard' => '']);
    $group = createBillingGroup();
    $group = Mockery::mock($group)->makePartial();
    $group->shouldReceive('subscribed')->andReturn(false);
    $group->shouldReceive('createOrGetStripeCustomer')->once();
    $group->shouldReceive('refresh')->once();

    expect(fn() => $this->service->createSubscriptionCheckout(
        $group,
        $this->user,
        BillingSubscriptionType::STANDARD,
    ))->toThrow(HttpException::class, 'Stripe の価格設定が完了していません。');
});

// ===== createPortalSession() メソッドのテストケース =====

test('4-3-5: 【Customer Portal】 Portal URL を返す', function () {
    $group = createBillingGroup(['stripe_id' => 'cus_portal_test']);
    $portalUrl = 'https://billing.stripe.com/p/session/test_portal';

    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('hasStripeId')->once()->andReturn(true);
    $mock->shouldReceive('billingPortalUrl')
        ->once()
        ->with('https://app.test.example/settings/billing')
        ->andReturn($portalUrl);

    $url = $this->service->createPortalSession($mock);

    expect($url)->toBe($portalUrl);
});

test('4-3-6: 【Customer Portal】 Stripe 未登録なら 422 を投げる', function () {
    $group = createBillingGroup(['stripe_id' => null]);

    expect(fn() => $this->service->createPortalSession($group))
        ->toThrow(HttpException::class, '課金情報が登録されていません。先にサブスクリプションまたは買い切りパックを購入してください。');
});

// ===== createPackCheckout() メソッドのテストケース =====

test('4-3-7: 【パック Checkout】 Checkout URL を返す', function () {
    $group = createBillingGroup();
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_light';
    $group = mockGroupForPackCheckout($group, BillingPackType::LIGHT, $checkoutUrl);

    $url = $this->service->createPackCheckout($group, $this->user, BillingPackType::LIGHT);

    expect($url)->toBe($checkoutUrl);
});

test('4-3-8: 【パック Checkout】 バリューパックの Checkout URL を返す', function () {
    $group = createBillingGroup();
    $checkoutUrl = 'https://checkout.stripe.com/c/pay/cs_test_value';
    $group = mockGroupForPackCheckout($group, BillingPackType::VALUE, $checkoutUrl);

    $url = $this->service->createPackCheckout($group, $this->user, BillingPackType::VALUE);

    expect($url)->toBe($checkoutUrl);
});

test('4-3-9: 【パック Checkout】 メタデータに type/group_id/credits がセットされる', function () {
    $group = createBillingGroup();
    $capturedMetadata = null;
    $capturedInvoiceCreation = null;

    $checkout = makeCheckout();
    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('checkout')
        ->once()
        ->withArgs(function ($items, array $sessionOptions) use (&$capturedMetadata, &$capturedInvoiceCreation) {
            $capturedMetadata = $sessionOptions['metadata'] ?? null;
            $capturedInvoiceCreation = $sessionOptions['invoice_creation'] ?? null;

            return true;
        })
        ->andReturn($checkout);
    $mock->shouldReceive('createOrGetStripeCustomer')->once();
    $mock->shouldReceive('refresh')->once();

    $this->service->createPackCheckout($mock, $this->user, BillingPackType::LIGHT);

    $expectedMetadata = [
        'type' => 'pack',
        'group_id' => $group->id,
        'credits' => (string) BillingPackType::LIGHT->credits(),
    ];

    expect($capturedMetadata)->toBe($expectedMetadata);
    expect($capturedInvoiceCreation)->toBe([
        'enabled' => true,
        'invoice_data' => [
            'metadata' => $expectedMetadata,
        ],
    ]);
});

test('4-3-10: 【パック Checkout】 価格 ID 未設定なら 500 を投げる（LIGHT）', function () {
    config(['billing.price_ids.pack_light' => '']);
    $group = createBillingGroup();
    $group = Mockery::mock($group)->makePartial();
    $group->shouldReceive('createOrGetStripeCustomer')->once();
    $group->shouldReceive('refresh')->once();

    expect(fn() => $this->service->createPackCheckout($group, $this->user, BillingPackType::LIGHT))
        ->toThrow(HttpException::class, 'Stripe の価格設定が完了していません。');
});

test('4-3-11: 【パック Checkout】 価格 ID 未設定なら 500 を投げる（VALUE）', function () {
    config(['billing.price_ids.pack_value' => '']);
    $group = createBillingGroup();
    $group = Mockery::mock($group)->makePartial();
    $group->shouldReceive('createOrGetStripeCustomer')->once();
    $group->shouldReceive('refresh')->once();

    expect(fn() => $this->service->createPackCheckout($group, $this->user, BillingPackType::VALUE))
        ->toThrow(HttpException::class, 'Stripe の価格設定が完了していません。');
});

// ===== getBillingStatus() メソッドのテストケース =====

function mockGroupDefaultPaymentMethod(Group $group, ?int $expMonth = null, ?int $expYear = null): Group
{
    /** @var Group&\Mockery\MockInterface $mock */
    $mock = Mockery::mock($group)->makePartial();

    if ($expMonth !== null || $expYear !== null) {
        $card = (object) [
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
        ];
        $stripePaymentMethod = (object) ['card' => $card];
        $paymentMethod = Mockery::mock();
        $paymentMethod->shouldReceive('asStripePaymentMethod')->andReturn($stripePaymentMethod);
        $mock->shouldReceive('defaultPaymentMethod')->andReturn($paymentMethod);
    } else {
        $mock->shouldReceive('defaultPaymentMethod')->andReturn(null);
    }

    return $mock;
}

test('4-3-12: 【課金状態取得】 未加入（FREE）の状態を返す', function () {
    $group = mockGroupDefaultPaymentMethod(createBillingGroup(['plan' => GroupPlan::FREE]));

    $status = $this->service->getBillingStatus($group);

    expect($status)->toBe([
        'plan' => 'free',
        'isSubscribed' => false,
        'subscriptionStatus' => null,
        'subscriptionEndsAt' => null,
        'pendingPlanChange' => null,
        'pmType' => null,
        'pmLastFour' => null,
        'pmExpMonth' => null,
        'pmExpYear' => null,
    ]);
});

test('4-3-13: 【課金状態取得】 サブスク中（active）の状態を返す', function () {
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'active',
        'ends_at' => null,
    ]);

    $status = $this->service->getBillingStatus(
        mockGroupDefaultPaymentMethod($group->fresh()),
    );

    expect($status['plan'])->toBe('standard')
        ->and($status['isSubscribed'])->toBeTrue()
        ->and($status['subscriptionStatus'])->toBe('active')
        ->and($status['pendingPlanChange'])->toBeNull()
        ->and($status['subscriptionEndsAt'])->toBeNull();
});

test('4-3-14: 【課金状態取得】 猶予期間中（Grace Period）の状態を返す', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $status = $this->service->getBillingStatus(
        mockGroupDefaultPaymentMethod($group->fresh()),
    );

    expect($status['isSubscribed'])->toBeTrue()
        ->and($status['pendingPlanChange'])->toBe([
            'nextPlan' => 'free',
            'changesAt' => $endsAt->toIso8601String(),
        ])
        ->and($status['subscriptionEndsAt'])->toBe($endsAt->toIso8601String());
});

test('4-3-15: 【課金状態取得】 キャンセル済み（猶予期間終了後）の状態を返す', function () {
    $endsAt = now()->subDay()->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $status = $this->service->getBillingStatus(
        mockGroupDefaultPaymentMethod($group->fresh()),
    );

    expect($status['isSubscribed'])->toBeFalse()
        ->and($status['pendingPlanChange'])->toBeNull()
        ->and($status['subscriptionStatus'])->toBe('canceled')
        ->and($status['subscriptionEndsAt'])->toBe($endsAt->toIso8601String());
});

test('4-3-16: 【課金状態取得】 pmType / pmLastFour / pmExpMonth / pmExpYear を返す', function () {
    $group = mockGroupDefaultPaymentMethod(
        createBillingGroup([
            'pm_type' => 'card',
            'pm_last_four' => '4242',
        ]),
        expMonth: 12,
        expYear: 2028,
    );

    $status = $this->service->getBillingStatus($group);

    expect($status['pmType'])->toBe('card')
        ->and($status['pmLastFour'])->toBe('4242')
        ->and($status['pmExpMonth'])->toBe(12)
        ->and($status['pmExpYear'])->toBe(2028);
});

test('4-3-17: 【課金状態取得】 plan が null のとき FREE を返す', function () {
    $group = createBillingGroup();
    $group->forceFill(['plan' => null]);

    $status = $this->service->getBillingStatus(
        mockGroupDefaultPaymentMethod($group),
    );

    expect($status['plan'])->toBe('free');
});

test('4-3-18: 【課金状態取得】 FREE に戻った後は ends_at が未来でも pendingPlanChange=null', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::FREE]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $status = $this->service->getBillingStatus(
        mockGroupDefaultPaymentMethod($group->fresh()),
    );

    expect($status['plan'])->toBe('free')
        ->and($status['pendingPlanChange'])->toBeNull()
        ->and($status['subscriptionEndsAt'])->toBe($endsAt->toIso8601String());
});

test('4-3-19: 【課金状態取得】 解約キャンセル後は pendingPlanChange=null かつ ends_at をクリアする', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'active',
        'ends_at' => $endsAt,
    ]);

    app(BillingWebhookService::class)->syncSubscriptionCancellationSchedule(
        $group->fresh(),
        [
            'status' => 'active',
            'cancel_at_period_end' => false,
            'metadata' => ['type' => 'default'],
            'items' => [
                'data' => [
                    ['price' => ['id' => config('billing.price_ids.subscription_standard')]],
                ],
            ],
        ],
    );

    $status = $this->service->getBillingStatus(
        mockGroupDefaultPaymentMethod($group->fresh()),
    );

    expect($status['pendingPlanChange'])->toBeNull()
        ->and($status['subscriptionEndsAt'])->toBeNull();
});

test('4-3-20: 【課金状態取得】 active かつ cancel_at_period_end=true で ends_at=null のとき解約予定を返す', function () {
    $periodEnd = now()->addDays(14)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'active',
        'ends_at' => null,
    ]);

    $subscription = $group->fresh()->subscription(config('billing.subscription_type'));

    $stripeSubscription = \Stripe\Subscription::constructFrom([
        'id' => $subscription->stripe_id,
        'cancel_at_period_end' => true,
        'current_period_end' => $periodEnd->timestamp,
    ]);

    /** @var \Laravel\Cashier\Subscription&\Mockery\MockInterface $mockSubscription */
    $mockSubscription = Mockery::mock($subscription)->makePartial();
    $mockSubscription->shouldReceive('asStripeSubscription')
        ->once()
        ->andReturn($stripeSubscription);

    /** @var Group&\Mockery\MockInterface $mockGroup */
    $mockGroup = Mockery::mock($group->fresh())->makePartial();
    $mockGroup->shouldReceive('subscription')
        ->with(config('billing.subscription_type'))
        ->andReturn($mockSubscription);
    $mockGroup->shouldReceive('subscribed')
        ->with(config('billing.subscription_type'))
        ->andReturn(true);
    $mockGroup->shouldReceive('defaultPaymentMethod')->andReturn(null);

    $status = $this->service->getBillingStatus($mockGroup);

    expect($status['pendingPlanChange'])->toBe([
        'nextPlan' => 'free',
        'changesAt' => $periodEnd->toIso8601String(),
    ])
        ->and($status['subscriptionEndsAt'])->toBe($periodEnd->toIso8601String());
});

test('4-3-21: 【課金状態取得】 cancel_at_period_end=true かつ current_period_end=null cancel_at ありで subscriptionEndsAt を返す', function () {
    $cancelAt = now()->addDays(22)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'active',
        'ends_at' => null,
    ]);

    $subscription = $group->fresh()->subscription(config('billing.subscription_type'));

    $stripeSubscription = \Stripe\Subscription::constructFrom([
        'id' => $subscription->stripe_id,
        'cancel_at_period_end' => true,
        'cancel_at' => $cancelAt->timestamp,
    ]);

    /** @var \Laravel\Cashier\Subscription&\Mockery\MockInterface $mockSubscription */
    $mockSubscription = Mockery::mock($subscription)->makePartial();
    $mockSubscription->shouldReceive('asStripeSubscription')
        ->once()
        ->andReturn($stripeSubscription);

    /** @var Group&\Mockery\MockInterface $mockGroup */
    $mockGroup = Mockery::mock($group->fresh())->makePartial();
    $mockGroup->shouldReceive('subscription')
        ->with(config('billing.subscription_type'))
        ->andReturn($mockSubscription);
    $mockGroup->shouldReceive('subscribed')
        ->with(config('billing.subscription_type'))
        ->andReturn(true);
    $mockGroup->shouldReceive('defaultPaymentMethod')->andReturn(null);

    $status = $this->service->getBillingStatus($mockGroup);

    expect($status['pendingPlanChange'])->toBe([
        'nextPlan' => 'free',
        'changesAt' => $cancelAt->toIso8601String(),
    ])
        ->and($status['subscriptionEndsAt'])->toBe($cancelAt->toIso8601String());
});

test('4-3-22: 【課金状態取得】 解約予定時に pendingPlanChange を返す', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $status = $this->service->getBillingStatus(
        mockGroupDefaultPaymentMethod($group->fresh()),
    );

    expect($status['pendingPlanChange'])->toBe([
        'nextPlan' => 'free',
        'changesAt' => $endsAt->toIso8601String(),
    ]);
});

test('4-3-23: 【課金状態取得】 予定変更なしのとき pendingPlanChange=null', function () {
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'active',
        'ends_at' => null,
    ]);

    $status = $this->service->getBillingStatus(
        mockGroupDefaultPaymentMethod($group->fresh()),
    );

    expect($status['pendingPlanChange'])->toBeNull();
});

// ===== resumeSubscription() メソッドのテストケース =====

test('4-3-24: 【プラン変更予定取り消し】 解約予定を取り消してサブスクを継続する', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $subscription = $group->fresh()->subscription(config('billing.subscription_type'));

    /** @var \Laravel\Cashier\Subscription&\Mockery\MockInterface $mockSubscription */
    $mockSubscription = Mockery::mock($subscription)->makePartial();
    $mockSubscription->shouldReceive('refresh')->andReturnSelf();
    $mockSubscription->shouldReceive('resume')->once();

    /** @var Group&\Mockery\MockInterface $mockGroup */
    $mockGroup = Mockery::mock($group->fresh())->makePartial();
    $mockGroup->shouldReceive('subscription')
        ->with(config('billing.subscription_type'))
        ->andReturn($mockSubscription);
    $mockGroup->shouldReceive('subscribed')
        ->with(config('billing.subscription_type'))
        ->andReturn(true);

    $this->service->resumeSubscription($mockGroup);
});

test('4-3-25: 【プラン変更予定取り消し】 予定変更なしなら 422 を投げる', function () {
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'stripe_status' => 'active',
        'ends_at' => null,
    ]);

    expect(fn () => $this->service->resumeSubscription($group->fresh()))
        ->toThrow(HttpException::class, '取り消すプラン変更予定がありません。');
});

function mockUpcomingInvoice(array $overrides = []): Invoice
{
    $date = \Illuminate\Support\Carbon::parse($overrides['date'] ?? '2024-02-01T00:00:00+00:00');
    $tax = $overrides['tax'] ?? 53;
    $stripeInvoice = (object) [
        'subtotal' => $overrides['subtotal'] ?? 580,
        'subtotal_excluding_tax' => $overrides['subtotalExcludingTax'] ?? 527,
        'total_taxes' => $overrides['totalTaxes'] ?? [
            (object) [
                'amount' => $tax,
                'tax_behavior' => 'inclusive',
                'type' => 'tax_rate_details',
            ],
        ],
    ];

    $lineItem = Mockery::mock();
    $lineItem->description = $overrides['lineDescription'] ?? 'スタンダードプラン';
    $lineItem->quantity = $overrides['lineQuantity'] ?? 1;
    $lineItem->amount = $overrides['lineAmount'] ?? 580;

    /** @var Invoice&\Mockery\MockInterface $mock */
    $mock = Mockery::mock(Invoice::class);
    $mock->shouldReceive('date')->andReturn($date);
    $mock->shouldReceive('invoiceLineItems')->andReturn([$lineItem]);
    $mock->shouldReceive('asStripeInvoice')->andReturn($stripeInvoice);
    $mock->shouldReceive('rawTotal')->andReturn($overrides['total'] ?? 580);
    $mock->shouldReceive('rawAmountDue')->andReturn($overrides['amountDue'] ?? 580);

    return $mock;
}

function mockPastInvoice(array $overrides = []): Invoice
{
    $date = \Illuminate\Support\Carbon::parse($overrides['date'] ?? '2024-01-01T00:00:00+00:00');
    $stripeInvoice = (object) [
        'hosted_invoice_url' => $overrides['invoiceUrl'] ?? 'https://invoice.stripe.com/i/test',
    ];

    /** @var Invoice&\Mockery\MockInterface $mock */
    $mock = Mockery::mock(Invoice::class);
    $mock->id = $overrides['id'] ?? 'in_test_1';
    $mock->shouldReceive('date')->andReturn($date);
    $mock->shouldReceive('rawTotal')->andReturn($overrides['total'] ?? 580);
    $mock->shouldReceive('asStripeInvoice')->andReturn($stripeInvoice);

    return $mock;
}

// ===== getInvoices() メソッドのテストケース =====

test('4-3-26: 【請求履歴取得】 次回お支払い予定と過去請求履歴を返す', function () {
    $group = createBillingGroup();
    $upcoming = mockUpcomingInvoice();
    $past = mockPastInvoice();

    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('hasStripeId')->once()->andReturn(true);
    $mock->shouldReceive('upcomingInvoice')->once()->andReturn($upcoming);
    $mock->shouldReceive('invoices')->once()->andReturn(collect([$past]));

    $result = $this->service->getInvoices($mock);

    expect($result)->toBe([
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
    ]);
});

test('4-3-27: 【請求履歴取得】 Stripe 未登録なら空を返す', function () {
    $group = createBillingGroup(['stripe_id' => null]);

    $result = $this->service->getInvoices($group);

    expect($result)->toBe([
        'upcomingInvoice' => null,
        'pastInvoices' => [],
    ]);
});

test('4-3-28: 【請求履歴取得】 upcoming 取得失敗時は null と pastInvoices を返す', function () {
    $group = createBillingGroup();
    $past = mockPastInvoice(['id' => 'in_test_2']);

    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('hasStripeId')->once()->andReturn(true);
    $mock->shouldReceive('upcomingInvoice')->once()->andThrow(new \Exception('No upcoming invoice'));
    $mock->shouldReceive('invoices')->once()->andReturn(collect([$past]));

    $result = $this->service->getInvoices($mock);

    expect($result['upcomingInvoice'])->toBeNull()
        ->and($result['pastInvoices'])->toBe([
            [
                'id' => 'in_test_2',
                'date' => '2024-01-01T00:00:00+00:00',
                'total' => 580,
                'invoiceUrl' => 'https://invoice.stripe.com/i/test',
            ],
        ]);
});

test('4-3-29: 【請求履歴取得】 upcoming が null のとき upcomingInvoice は null', function () {
    $group = createBillingGroup();
    $past = mockPastInvoice();

    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('hasStripeId')->once()->andReturn(true);
    $mock->shouldReceive('upcomingInvoice')->once()->andReturn(null);
    $mock->shouldReceive('invoices')->once()->andReturn(collect([$past]));

    $result = $this->service->getInvoices($mock);

    expect($result['upcomingInvoice'])->toBeNull()
        ->and($result['pastInvoices'])->toHaveCount(1);
});

test('4-3-30: 【請求履歴取得】 過去請求がない場合 pastInvoices は空配列', function () {
    $group = createBillingGroup();
    $upcoming = mockUpcomingInvoice();

    $mock = Mockery::mock($group)->makePartial();
    $mock->shouldReceive('hasStripeId')->once()->andReturn(true);
    $mock->shouldReceive('upcomingInvoice')->once()->andReturn($upcoming);
    $mock->shouldReceive('invoices')->once()->andReturn(collect());

    $result = $this->service->getInvoices($mock);

    expect($result['upcomingInvoice'])->not->toBeNull()
        ->and($result['pastInvoices'])->toBe([]);
});
