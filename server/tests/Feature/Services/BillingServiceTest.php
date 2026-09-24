<?php

use App\Enums\BillingPackType;
use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Models\Group;
use App\Models\User;
use App\Services\BillingService;
use App\Services\PayjpBillingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Payjp\Card;
use Payjp\Charge;
use Payjp\Customer;
use Payjp\Error\Base as PayjpError;
use Payjp\Subscription;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'billing.subscription_plan_ids.standard' => 'pln_test_standard',
        'billing.subscription_amounts.standard' => 580,
        'billing.pack_prices.light' => 400,
        'billing.pack_prices.value' => 800,
        'billing.pack_credits.light' => 10,
        'billing.pack_credits.value' => 30,
        'app.timezone' => 'Asia/Tokyo',
    ]);

    $this->user = User::factory()->create();
});

function billingService(): BillingService
{
    return app(BillingService::class);
}

function createBillingGroup(array $attributes = []): Group
{
    return Group::factory()->create(array_merge([
        'plan' => GroupPlan::FREE,
        'payjp_customer_id' => null,
    ], $attributes));
}

function createSubscriptionRecord(Group $group, array $attributes = []): void
{
    $group->subscriptions()->create(array_merge([
        'payjp_subscription_id' => 'sub_test_' . str()->random(8),
        'payjp_plan_id' => config('billing.subscription_plan_ids.standard'),
        'status' => 'active',
        'ends_at' => null,
        'current_period_end' => null,
    ], $attributes));
}

function makePayjpCard(string $cardId, array $attributes = []): Card
{
    $card = new Card($cardId);
    $card->brand = $attributes['brand'] ?? 'Visa';
    $card->last4 = $attributes['last4'] ?? '4242';
    $card->exp_month = $attributes['exp_month'] ?? 12;
    $card->exp_year = $attributes['exp_year'] ?? 2028;

    return $card;
}

function makePayjpCustomer(array $overrides = []): Customer
{
    $cardId = $overrides['cardId'] ?? 'car_test_1';
    unset($overrides['cardId']);
    $cardAttributes = $overrides['card'] ?? [];
    unset($overrides['card']);

    $customerId = $overrides['id'] ?? 'cus_test_123';
    unset($overrides['id']);

    $customer = new Customer($customerId);
    $customer->default_card = $cardId;
    $customer->cards = (object) ['data' => [makePayjpCard($cardId, $cardAttributes)]];

    foreach ($overrides as $key => $value) {
        $customer->{$key} = $value;
    }

    return $customer;
}

function makePayjpSubscription(array $overrides = []): Subscription
{
    $planId = config('billing.subscription_plan_ids.standard');
    $subscriptionId = $overrides['id'] ?? 'sub_test_' . str()->random(8);
    unset($overrides['id']);

    $subscription = new Subscription($subscriptionId);
    $subscription->status = $overrides['status'] ?? 'active';
    unset($overrides['status']);
    $subscription->current_period_start = $overrides['current_period_start'] ?? now()->timestamp;
    unset($overrides['current_period_start']);
    $subscription->current_period_end = $overrides['current_period_end'] ?? now()->addMonth()->startOfSecond()->timestamp;
    unset($overrides['current_period_end']);
    $subscription->trial_end = $overrides['trial_end'] ?? null;
    unset($overrides['trial_end']);
    $subscription->canceled_at = $overrides['canceled_at'] ?? null;
    unset($overrides['canceled_at']);
    $subscription->paused_at = $overrides['paused_at'] ?? null;
    unset($overrides['paused_at']);
    $subscription->plan = $overrides['plan'] ?? (object) ['id' => $planId];
    unset($overrides['plan']);

    foreach ($overrides as $key => $value) {
        $subscription->{$key} = $value;
    }

    return $subscription;
}

function makePayjpCharge(array $overrides = []): Charge
{
    $chargeId = $overrides['id'] ?? 'ch_test_' . str()->random(8);
    unset($overrides['id']);

    $charge = new Charge($chargeId);

    foreach ($overrides as $key => $value) {
        $charge->{$key} = $value;
    }

    return $charge;
}

function makePayjpError(int $httpStatus = 402): PayjpError
{
    $error = Mockery::mock(PayjpError::class);
    $error->shouldReceive('getHttpStatus')->andReturn($httpStatus);

    return $error;
}

// ===== updateCard() メソッドのテストケース =====

test('4-3-1: 【カード更新】 課金状態配列を返し pm 情報を同期する', function () {
    $group = createBillingGroup([
        'payjp_customer_id' => 'cus_update_test',
        'plan' => GroupPlan::FREE,
    ]);
    $customer = makePayjpCustomer([
        'id' => 'cus_update_test',
        'card' => ['brand' => 'Visa', 'last4' => '4242'],
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($customer) {
        $mock->shouldReceive('addCustomerCardFromToken')
            ->once()
            ->with('cus_update_test', 'tok_test_update')
            ->andReturn($customer);
    });

    $status = billingService()->updateCard($group, $this->user, 'tok_test_update');

    $group->refresh();
    expect($group->pm_type)->toBe('Visa')
        ->and($group->pm_last_four)->toBe('4242')
        ->and($group->pm_exp_month)->toBe(12)
        ->and($group->pm_exp_year)->toBe(2028)
        ->and($status['plan'])->toBe('free')
        ->and($status['isSubscribed'])->toBeFalse()
        ->and($status['pmType'])->toBe('Visa')
        ->and($status['pmLastFour'])->toBe('4242')
        ->and($status['pmExpMonth'])->toBe(12)
        ->and($status['pmExpYear'])->toBe(2028);
});

test('4-3-2: 【カード更新】 Customer 未作成時は新規作成する', function () {
    $group = createBillingGroup(['payjp_customer_id' => null]);
    $customer = makePayjpCustomer(['id' => 'cus_new_test']);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($group, $customer) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with([
                'email' => $this->user->email,
                'description' => $this->user->name,
                'card' => 'tok_test_new',
                'metadata' => ['group_id' => $group->id],
            ])
            ->andReturn($customer);
    });

    $status = billingService()->updateCard($group, $this->user, 'tok_test_new');

    $group->refresh();
    expect($group->payjp_customer_id)->toBe('cus_new_test')
        ->and($status['pmLastFour'])->toBe('4242');
});

test('4-3-3: 【カード更新】 PAY.JP API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup(['payjp_customer_id' => 'cus_fail_test']);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldReceive('addCustomerCardFromToken')
            ->once()
            ->andThrow(makePayjpError());
    });

    expect(fn() => billingService()->updateCard($group, $this->user, 'tok_fail'))
        ->toThrow(HttpException::class, 'カード情報の更新に失敗しました。');
});

// ===== deleteCard() メソッドのテストケース =====

test('4-3-4: 【カード削除】 デフォルトカードを削除し pm をクリアする', function () {
    $group = createBillingGroup([
        'payjp_customer_id' => 'cus_delete_test',
        'pm_type' => 'Visa',
        'pm_last_four' => '4242',
        'pm_exp_month' => 12,
        'pm_exp_year' => 2028,
    ]);
    $customer = makePayjpCustomer(['id' => 'cus_delete_test', 'cardId' => 'car_delete_1']);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($customer) {
        $mock->shouldReceive('retrieveCustomer')
            ->once()
            ->with('cus_delete_test')
            ->andReturn($customer);
        $mock->shouldReceive('deleteCustomerCard')
            ->once()
            ->with('cus_delete_test', 'car_delete_1');
    });

    billingService()->deleteCard($group);

    $group->refresh();
    expect($group->pm_type)->toBeNull()
        ->and($group->pm_last_four)->toBeNull()
        ->and($group->pm_exp_month)->toBeNull()
        ->and($group->pm_exp_year)->toBeNull();
});

test('4-3-5: 【カード削除】 Customer 未登録なら 422 を投げる', function () {
    $group = createBillingGroup(['payjp_customer_id' => null]);

    expect(fn() => billingService()->deleteCard($group))
        ->toThrow(HttpException::class, '課金情報が登録されていません。');
});

test('4-3-6: 【カード削除】 PAY.JP API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup(['payjp_customer_id' => 'cus_delete_fail']);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldReceive('retrieveCustomer')
            ->once()
            ->andThrow(makePayjpError());
    });

    expect(fn() => billingService()->deleteCard($group))
        ->toThrow(HttpException::class, 'カード情報の削除に失敗しました。');
});

// ===== createSubscription() メソッドのテストケース =====

test('4-3-7: 【サブスク開始】 Customer 未作成時にサブスクを開始し課金状態を返す', function () {
    $group = createBillingGroup(['payjp_customer_id' => null, 'ai_monthly_remaining' => 0]);
    $customer = makePayjpCustomer(['id' => 'cus_subscribe_new']);
    $periodEnd = now()->addMonth()->startOfSecond();
    $payjpSubscription = makePayjpSubscription([
        'id' => 'sub_subscribe_new',
        'current_period_end' => $periodEnd->timestamp,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($group, $customer, $payjpSubscription) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with([
                'email' => $this->user->email,
                'description' => $this->user->name,
                'card' => 'tok_subscribe_new',
                'metadata' => ['group_id' => $group->id],
            ])
            ->andReturn($customer);
        $mock->shouldReceive('createSubscription')
            ->once()
            ->with([
                'customer' => 'cus_subscribe_new',
                'plan' => config('billing.subscription_plan_ids.standard'),
                'metadata' => ['group_id' => $group->id],
            ])
            ->andReturn($payjpSubscription);
    });

    $status = billingService()->createSubscription(
        $group,
        $this->user,
        BillingSubscriptionType::STANDARD,
        'tok_subscribe_new',
    );

    $group->refresh();
    $subscription = $group->subscription();

    expect($group->payjp_customer_id)->toBe('cus_subscribe_new')
        ->and($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->pm_last_four)->toBe('4242')
        ->and($subscription)->not->toBeNull()
        ->and($subscription->payjp_subscription_id)->toBe('sub_subscribe_new')
        ->and($subscription->status)->toBe('active')
        ->and($status['plan'])->toBe('standard')
        ->and($status['isSubscribed'])->toBeTrue()
        ->and($status['subscriptionStatus'])->toBe('active');
});

test('4-3-8: 【サブスク開始】 既存 Customer でサブスクを開始する', function () {
    $group = createBillingGroup(['payjp_customer_id' => 'cus_subscribe_existing']);
    $customer = makePayjpCustomer(['id' => 'cus_subscribe_existing']);
    $payjpSubscription = makePayjpSubscription(['id' => 'sub_subscribe_existing']);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($customer, $payjpSubscription) {
        $mock->shouldReceive('addCustomerCardFromToken')
            ->once()
            ->with('cus_subscribe_existing', 'tok_subscribe_existing')
            ->andReturn($customer);
        $mock->shouldReceive('createSubscription')
            ->once()
            ->andReturn($payjpSubscription);
    });

    billingService()->createSubscription(
        $group,
        $this->user,
        BillingSubscriptionType::STANDARD,
        'tok_subscribe_existing',
    );

    expect($group->fresh()->subscription()?->payjp_subscription_id)->toBe('sub_subscribe_existing');
});

test('4-3-9: 【サブスク開始】 current_period_end ありで月間残数をリセットする', function () {
    $periodEnd = now()->addMonth()->startOfSecond();
    $group = createBillingGroup([
        'payjp_customer_id' => null,
        'ai_monthly_remaining' => 0,
    ]);
    $customer = makePayjpCustomer(['id' => 'cus_subscribe_renew']);
    $payjpSubscription = makePayjpSubscription([
        'id' => 'sub_subscribe_renew',
        'current_period_end' => $periodEnd->timestamp,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($group, $customer, $payjpSubscription) {
        $mock->shouldReceive('createCustomer')->andReturn($customer);
        $mock->shouldReceive('createSubscription')->andReturn($payjpSubscription);
    });

    billingService()->createSubscription(
        $group,
        $this->user,
        BillingSubscriptionType::STANDARD,
        'tok_subscribe_renew',
    );

    $group->refresh();
    expect($group->ai_monthly_remaining)->toBe(GroupPlan::STANDARD->monthlyLimit())
        ->and($group->ai_usage_reset_at?->toIso8601String())->toBe($periodEnd->toIso8601String());
});

test('4-3-10: 【サブスク開始】 既にサブスク済みなら 422 を投げる', function () {
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, ['status' => 'active']);

    expect(fn() => billingService()->createSubscription(
        $group->fresh(),
        $this->user,
        BillingSubscriptionType::STANDARD,
        'tok_already_subscribed',
    ))->toThrow(HttpException::class, 'すでにサブスクリプションに加入しています。');
});

test('4-3-11: 【サブスク開始】 PAY.JP API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup(['payjp_customer_id' => null]);
    $customer = makePayjpCustomer(['id' => 'cus_subscribe_fail']);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($customer) {
        $mock->shouldReceive('createCustomer')->andReturn($customer);
        $mock->shouldReceive('createSubscription')
            ->once()
            ->andThrow(makePayjpError());
    });

    expect(fn() => billingService()->createSubscription(
        $group,
        $this->user,
        BillingSubscriptionType::STANDARD,
        'tok_subscribe_fail',
    ))->toThrow(HttpException::class, 'サブスクリプションの開始に失敗しました。');
});

// ===== cancelSubscription() メソッドのテストケース =====

test('4-3-12: 【サブスク解約】 期間終了解約し課金状態配列を返す', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();
    $payjpSubId = 'sub_cancel_test';
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD, 'payjp_customer_id' => 'cus_sub_cancel']);
    createSubscriptionRecord($group, [
        'payjp_subscription_id' => $payjpSubId,
        'status' => 'active',
        'ends_at' => null,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($payjpSubId, $periodEnd) {
        $mock->shouldReceive('cancelSubscription')
            ->once()
            ->with($payjpSubId)
            ->andReturn(makePayjpSubscription([
                'id' => $payjpSubId,
                'status' => 'canceled',
                'current_period_end' => $periodEnd->timestamp,
            ]));
    });

    $status = billingService()->cancelSubscription($group->fresh());

    expect($status['plan'])->toBe('standard')
        ->and($status['isSubscribed'])->toBeTrue()
        ->and($status['subscriptionStatus'])->toBe('canceled')
        ->and($status['pendingPlanChange'])->toBe([
            'nextPlan' => 'free',
            'changesAt' => $periodEnd->toIso8601String(),
        ])
        ->and($status['subscriptionEndsAt'])->toBe($periodEnd->toIso8601String());
});

test('4-3-13: 【サブスク解約】 未加入なら 422 を投げる', function () {
    $group = createBillingGroup(['plan' => GroupPlan::FREE]);

    expect(fn() => billingService()->cancelSubscription($group))
        ->toThrow(HttpException::class, '有効なサブスクリプションがありません。');
});

test('4-3-14: 【サブスク解約】 PAY.JP API 失敗時は HttpException を投げる', function () {
    $payjpSubId = 'sub_cancel_fail';
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'payjp_subscription_id' => $payjpSubId,
        'status' => 'active',
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($payjpSubId) {
        $mock->shouldReceive('cancelSubscription')
            ->once()
            ->with($payjpSubId)
            ->andThrow(makePayjpError());
    });

    expect(fn() => billingService()->cancelSubscription($group->fresh()))
        ->toThrow(HttpException::class, 'サブスクリプションの解約に失敗しました。');
});

// ===== purchasePack() メソッドのテストケース =====

test('4-3-15: 【パック購入】 カードトークンでライトパックを購入し ai_pack_remaining を加算する', function () {
    $group = createBillingGroup(['payjp_customer_id' => null, 'ai_pack_remaining' => 5]);
    $customer = makePayjpCustomer(['id' => 'cus_pack_light']);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($group, $customer) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with([
                'email' => $this->user->email,
                'description' => $this->user->name,
                'card' => 'tok_pack_light',
                'metadata' => ['group_id' => $group->id],
            ])
            ->andReturn($customer);
        $mock->shouldReceive('createCharge')
            ->once()
            ->with([
                'amount' => 400,
                'currency' => 'jpy',
                'customer' => 'cus_pack_light',
                'metadata' => [
                    'type' => 'pack',
                    'group_id' => $group->id,
                    'pack_type' => 'light',
                    'credits' => '10',
                ],
            ])
            ->andReturn(makePayjpCharge(['id' => 'ch_pack_light']));
    });

    $status = billingService()->purchasePack(
        $group,
        $this->user,
        BillingPackType::LIGHT,
        'tok_pack_light',
    );

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(15)
        ->and($group->payjp_customer_id)->toBe('cus_pack_light')
        ->and($status['plan'])->toBe('free')
        ->and($status['isSubscribed'])->toBeFalse();
});

test('4-3-16: 【パック購入】 カードトークンでバリューパックを購入する', function () {
    $group = createBillingGroup(['payjp_customer_id' => null, 'ai_pack_remaining' => 0]);
    $customer = makePayjpCustomer(['id' => 'cus_pack_value']);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($customer) {
        $mock->shouldReceive('createCustomer')->andReturn($customer);
        $mock->shouldReceive('createCharge')
            ->once()
            ->with(Mockery::on(function (array $params): bool {
                return $params['amount'] === 800
                    && $params['metadata']['pack_type'] === 'value'
                    && $params['metadata']['credits'] === '30';
            }))
            ->andReturn(makePayjpCharge(['id' => 'ch_pack_value']));
    });

    billingService()->purchasePack(
        $group,
        $this->user,
        BillingPackType::VALUE,
        'tok_pack_value',
    );

    expect($group->fresh()->ai_pack_remaining)->toBe(30);
});

test('4-3-17: 【パック購入】 登録済みカードでトークンなし購入する', function () {
    $group = createBillingGroup([
        'payjp_customer_id' => 'cus_pack_existing',
        'ai_pack_remaining' => 2,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldNotReceive('createCustomer');
        $mock->shouldNotReceive('addCustomerCardFromToken');
        $mock->shouldReceive('createCharge')
            ->once()
            ->with(Mockery::on(fn(array $params): bool => $params['customer'] === 'cus_pack_existing'))
            ->andReturn(makePayjpCharge(['id' => 'ch_pack_existing']));
    });

    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT);

    expect($group->fresh()->ai_pack_remaining)->toBe(12);
});

test('4-3-18: 【パック購入】 Customer 未登録かつトークンなしなら 422 を投げる', function () {
    $group = createBillingGroup(['payjp_customer_id' => null]);

    expect(fn() => billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT))
        ->toThrow(HttpException::class, '支払い方法が登録されていません。カード情報を登録してください。');
});

test('4-3-19: 【パック購入】 PAY.JP API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup(['payjp_customer_id' => 'cus_pack_charge_fail']);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldReceive('createCharge')
            ->once()
            ->andThrow(makePayjpError());
    });

    expect(fn() => billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT))
        ->toThrow(HttpException::class, '買い切りパックの購入処理に失敗しました。');
});

test('4-3-20: 【パック購入】 空文字トークンは未指定として扱い登録済み Customer で課金する', function () {
    $group = createBillingGroup([
        'payjp_customer_id' => 'cus_pack_empty_token',
        'ai_pack_remaining' => 0,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldNotReceive('createCustomer');
        $mock->shouldNotReceive('addCustomerCardFromToken');
        $mock->shouldReceive('createCharge')->once()->andReturn(makePayjpCharge(['id' => 'ch_empty_token']));
    });

    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT, '');

    expect($group->fresh()->ai_pack_remaining)->toBe(10);
});

test('4-3-21: 【パック購入】 複数回購入で ai_pack_remaining が累積する', function () {
    $group = createBillingGroup([
        'payjp_customer_id' => 'cus_pack_accumulate',
        'ai_pack_remaining' => 5,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldReceive('createCharge')
            ->twice()
            ->andReturn(makePayjpCharge(['id' => 'ch_accumulate']));
    });

    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT);
    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT);

    expect($group->fresh()->ai_pack_remaining)->toBe(25);
});

test('4-3-22: 【パック購入】 カード登録時の PAY.JP API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup(['payjp_customer_id' => null]);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->andThrow(makePayjpError());
    });

    expect(fn() => billingService()->purchasePack(
        $group,
        $this->user,
        BillingPackType::LIGHT,
        'tok_pack_customer_fail',
    ))->toThrow(HttpException::class, '買い切りパックの購入処理に失敗しました。');
});

// ===== getBillingStatus() メソッドのテストケース =====

test('4-3-23: 【課金状態取得】 未加入（FREE）の状態を返す', function () {
    $group = createBillingGroup(['plan' => GroupPlan::FREE]);

    $status = billingService()->getBillingStatus($group);

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

test('4-3-24: 【課金状態取得】 サブスク中（active）の状態を返す', function () {
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'ends_at' => null,
    ]);

    $status = billingService()->getBillingStatus($group->fresh());

    expect($status['plan'])->toBe('standard')
        ->and($status['isSubscribed'])->toBeTrue()
        ->and($status['subscriptionStatus'])->toBe('active')
        ->and($status['pendingPlanChange'])->toBeNull()
        ->and($status['subscriptionEndsAt'])->toBeNull();
});

test('4-3-25: 【課金状態取得】 猶予期間中（Grace Period）の状態を返す', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $status = billingService()->getBillingStatus($group->fresh());

    expect($status['isSubscribed'])->toBeTrue()
        ->and($status['pendingPlanChange'])->toBe([
            'nextPlan' => 'free',
            'changesAt' => $endsAt->toIso8601String(),
        ])
        ->and($status['subscriptionEndsAt'])->toBe($endsAt->toIso8601String());
});

test('4-3-26: 【課金状態取得】 キャンセル済み（猶予期間終了後）の状態を返す', function () {
    $endsAt = now()->subDay()->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $status = billingService()->getBillingStatus($group->fresh());

    expect($status['isSubscribed'])->toBeFalse()
        ->and($status['pendingPlanChange'])->toBeNull()
        ->and($status['subscriptionStatus'])->toBe('canceled')
        ->and($status['subscriptionEndsAt'])->toBe($endsAt->toIso8601String());
});

test('4-3-27: 【課金状態取得】 pmType / pmLastFour / pmExpMonth / pmExpYear を返す', function () {
    $group = createBillingGroup([
        'payjp_customer_id' => 'cus_pm_test',
        'pm_type' => 'Visa',
        'pm_last_four' => '4242',
        'pm_exp_month' => 12,
        'pm_exp_year' => 2028,
    ]);

    $status = billingService()->getBillingStatus($group);

    expect($status['pmType'])->toBe('Visa')
        ->and($status['pmLastFour'])->toBe('4242')
        ->and($status['pmExpMonth'])->toBe(12)
        ->and($status['pmExpYear'])->toBe(2028);
});

test('4-3-28: 【課金状態取得】 plan が null のとき FREE を返す', function () {
    $group = createBillingGroup();
    $group->forceFill(['plan' => null]);

    $status = billingService()->getBillingStatus($group);

    expect($status['plan'])->toBe('free');
});

test('4-3-29: 【課金状態取得】 FREE に戻った後は ends_at が未来でも pendingPlanChange=null', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::FREE]);
    createSubscriptionRecord($group, [
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $status = billingService()->getBillingStatus($group->fresh());

    expect($status['plan'])->toBe('free')
        ->and($status['pendingPlanChange'])->toBeNull()
        ->and($status['subscriptionEndsAt'])->toBe($endsAt->toIso8601String());
});

test('4-3-30: 【課金状態取得】 解約取り消し同期後は pendingPlanChange=null かつ ends_at をクリアする', function () {
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'ends_at' => null,
    ]);

    $status = billingService()->getBillingStatus($group->fresh());

    expect($status['pendingPlanChange'])->toBeNull()
        ->and($status['subscriptionEndsAt'])->toBeNull();
});

test('4-3-31: 【課金状態取得】 active かつ ends_at が未来のとき解約予定を返す', function () {
    $endsAt = now()->addDays(14)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'ends_at' => $endsAt,
    ]);

    $status = billingService()->getBillingStatus($group->fresh());

    expect($status['pendingPlanChange'])->toBe([
        'nextPlan' => 'free',
        'changesAt' => $endsAt->toIso8601String(),
    ])
        ->and($status['subscriptionEndsAt'])->toBe($endsAt->toIso8601String());
});

test('4-3-32: 【課金状態取得】 canceled かつ ends_at が未来のとき解約予定を返す', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $status = billingService()->getBillingStatus($group->fresh());

    expect($status['pendingPlanChange'])->toBe([
        'nextPlan' => 'free',
        'changesAt' => $endsAt->toIso8601String(),
    ]);
});

test('4-3-33: 【課金状態取得】 予定変更なしのとき pendingPlanChange=null', function () {
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'ends_at' => null,
    ]);

    $status = billingService()->getBillingStatus($group->fresh());

    expect($status['pendingPlanChange'])->toBeNull();
});

// ===== resumeSubscription() メソッドのテストケース =====

test('4-3-34: 【プラン変更予定取り消し】 解約予定を取り消してサブスクを継続する', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $payjpSubId = 'sub_resume_test';
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'payjp_subscription_id' => $payjpSubId,
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($payjpSubId) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->with($payjpSubId)
            ->andReturn(makePayjpSubscription([
                'id' => $payjpSubId,
                'status' => 'active',
                'current_period_end' => now()->addMonth()->timestamp,
            ]));
    });

    billingService()->resumeSubscription($group->fresh());

    $subscription = $group->fresh()->subscription();
    expect($subscription->status)->toBe('active')
        ->and($subscription->ends_at)->toBeNull();
});

test('4-3-35: 【プラン変更予定取り消し】 予定変更なしなら 422 を投げる', function () {
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'ends_at' => null,
    ]);

    expect(fn() => billingService()->resumeSubscription($group->fresh()))
        ->toThrow(HttpException::class, '取り消すプラン変更予定がありません。');
});

test('4-3-36: 【プラン変更予定取り消し】 PAY.JP API 失敗時は HttpException を投げる', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $payjpSubId = 'sub_resume_fail';
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'payjp_subscription_id' => $payjpSubId,
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($payjpSubId) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->with($payjpSubId)
            ->andThrow(makePayjpError());
    });

    expect(fn() => billingService()->resumeSubscription($group->fresh()))
        ->toThrow(HttpException::class, 'プラン変更予定の取り消しに失敗しました。');
});

// ===== getInvoices() メソッドのテストケース =====

test('4-3-37: 【請求履歴取得】 次回お支払い予定と過去 Charge 履歴を返す', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();
    $chargeCreated = now()->subMonth()->startOfSecond();
    $group = createBillingGroup(['payjp_customer_id' => 'cus_invoices', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'current_period_end' => $periodEnd,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($chargeCreated) {
        $mock->shouldReceive('listCharges')
            ->once()
            ->andReturn([
                (object) [
                    'id' => 'ch_test_paid',
                    'paid' => true,
                    'amount' => 580,
                    'created' => $chargeCreated->timestamp,
                ],
            ]);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['upcomingInvoice'])->toBe([
        'date' => $periodEnd->toIso8601String(),
        'lines' => [
            [
                'description' => 'スタンダードプラン',
                'quantity' => 1,
                'amount' => 580,
            ],
        ],
        'subtotal' => 580,
        'subtotalExcludingTax' => 580,
        'tax' => 0,
        'total' => 580,
        'amountDue' => 580,
    ])
        ->and($result['pastInvoices'])->toBe([
            [
                'id' => 'ch_test_paid',
                'date' => $chargeCreated->toIso8601String(),
                'description' => 'スタンダードプラン',
                'total' => 580,
            ],
        ]);
});

test('4-3-38: 【請求履歴取得】 Customer 未登録なら空を返す', function () {
    $group = createBillingGroup(['payjp_customer_id' => null]);

    $result = billingService()->getInvoices($group);

    expect($result)->toBe([
        'upcomingInvoice' => null,
        'pastInvoices' => [],
    ]);
});

test('4-3-39: 【請求履歴取得】 解約猶予中（!isActive）は upcomingInvoice は null', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['payjp_customer_id' => 'cus_grace', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldReceive('listCharges')
            ->once()
            ->andReturn([
                (object) [
                    'id' => 'ch_grace_paid',
                    'paid' => true,
                    'amount' => 400,
                    'created' => now()->subWeek()->timestamp,
                ],
            ]);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['upcomingInvoice'])->toBeNull()
        ->and($result['pastInvoices'])->toHaveCount(1)
        ->and($result['pastInvoices'][0]['id'])->toBe('ch_grace_paid');
});

test('4-3-40: 【請求履歴取得】 paid=false の Charge は pastInvoices に含めない', function () {
    $group = createBillingGroup(['payjp_customer_id' => 'cus_paid_filter', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, ['status' => 'active']);

    $paidAt = now()->subDays(3)->startOfSecond();

    $this->mock(PayjpBillingClient::class, function ($mock) use ($paidAt) {
        $mock->shouldReceive('listCharges')
            ->once()
            ->andReturn([
                (object) [
                    'id' => 'ch_unpaid',
                    'paid' => false,
                    'amount' => 580,
                    'created' => now()->timestamp,
                ],
                (object) [
                    'id' => 'ch_paid_only',
                    'paid' => true,
                    'amount' => 580,
                    'created' => $paidAt->timestamp,
                ],
            ]);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['pastInvoices'])->toHaveCount(1)
        ->and($result['pastInvoices'][0]['id'])->toBe('ch_paid_only');
});

test('4-3-41: 【請求履歴取得】 PAY.JP 取得失敗時は upcoming と past を安全に返す', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();
    $group = createBillingGroup(['payjp_customer_id' => 'cus_list_fail', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'current_period_end' => $periodEnd,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldReceive('listCharges')
            ->once()
            ->andThrow(makePayjpError());
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['upcomingInvoice'])->not->toBeNull()
        ->and($result['upcomingInvoice']['date'])->toBe($periodEnd->toIso8601String())
        ->and($result['pastInvoices'])->toBe([]);
});

test('4-3-42: 【請求履歴取得】 Charge がない場合 pastInvoices は空配列', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();
    $group = createBillingGroup(['payjp_customer_id' => 'cus_no_charges', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'current_period_end' => $periodEnd,
    ]);

    $this->mock(PayjpBillingClient::class, function ($mock) {
        $mock->shouldReceive('listCharges')
            ->once()
            ->andReturn([]);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['upcomingInvoice'])->not->toBeNull()
        ->and($result['pastInvoices'])->toBe([]);
});

test('4-3-43: 【請求履歴取得】 パック購入 Charge の description にクレジット数が含まれる', function () {
    $chargeCreated = now()->subDays(5)->startOfSecond();
    $group = createBillingGroup(['payjp_customer_id' => 'cus_pack_desc', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, ['status' => 'active']);

    $this->mock(PayjpBillingClient::class, function ($mock) use ($chargeCreated) {
        $mock->shouldReceive('listCharges')
            ->once()
            ->andReturn([
                (object) [
                    'id' => 'ch_pack_light',
                    'paid' => true,
                    'amount' => 400,
                    'created' => $chargeCreated->timestamp,
                    'metadata' => (object) [
                        'type' => 'pack',
                        'pack_type' => 'light',
                        'credits' => '10',
                        'group_id' => '1',
                    ],
                ],
            ]);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['pastInvoices'])->toHaveCount(1)
        ->and($result['pastInvoices'][0]['description'])->toBe('買い切りパック ライト');
});
