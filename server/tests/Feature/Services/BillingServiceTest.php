<?php

use App\Enums\BillingPackType;
use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Models\BillingHistory;
use App\Models\Group;
use App\Models\User;
use App\Services\BillingService;
use App\Services\SquareBillingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Square\Exceptions\SquareApiException;
use Square\Types\Card as SquareCard;
use Square\Types\Customer as SquareCustomer;
use Square\Types\Money;
use Square\Types\Payment as SquarePayment;
use Square\Types\Subscription as SquareSubscription;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'billing.subscription_plan_ids.standard' => 'plan_var_test_standard',
        'billing.subscription_amounts.standard' => 580,
        'billing.pack_prices.light' => 400,
        'billing.pack_prices.value' => 800,
        'billing.pack_credits.light' => 10,
        'billing.pack_credits.value' => 30,
        'services.square.location_id' => 'loc_test_123',
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
        'square_customer_id' => null,
    ], $attributes));
}

function createSubscriptionRecord(Group $group, array $attributes = []): void
{
    $group->subscriptions()->create(array_merge([
        'square_subscription_id' => 'sub_test_' . str()->random(8),
        'square_plan_variation_id' => config('billing.subscription_plan_ids.standard'),
        'status' => 'active',
        'ends_at' => null,
        'current_period_end' => null,
    ], $attributes));
}

function makeSquareCard(string $cardId, array $attributes = []): SquareCard
{
    return new SquareCard([
        'id' => $cardId,
        'cardBrand' => $attributes['cardBrand'] ?? 'VISA',
        'last4' => $attributes['last4'] ?? '4242',
        'expMonth' => $attributes['expMonth'] ?? 12,
        'expYear' => $attributes['expYear'] ?? 2028,
        'customerId' => $attributes['customerId'] ?? null,
    ]);
}

function makeSquareCustomer(array $overrides = []): SquareCustomer
{
    $customerId = $overrides['id'] ?? 'cus_test_123';
    unset($overrides['id']);

    return new SquareCustomer(array_merge(['id' => $customerId], $overrides));
}

function makeSquareApiException(int $statusCode = 402): SquareApiException
{
    return new SquareApiException('Square API error', $statusCode, null);
}

function makeSquareSubscription(array $overrides = []): SquareSubscription
{
    $planId = config('billing.subscription_plan_ids.standard');
    $subscriptionId = $overrides['id'] ?? 'sub_test_' . str()->random(8);
    unset($overrides['id']);

    if (isset($overrides['status'])) {
        $overrides['status'] = strtoupper((string) $overrides['status']);
    }

    if (isset($overrides['current_period_end'])) {
        $periodEnd = \Carbon\Carbon::createFromTimestamp(
            (int) $overrides['current_period_end'],
            config('app.timezone'),
        );
        $overrides['chargedThroughDate'] = $periodEnd->format('Y-m-d');
        unset($overrides['current_period_end']);
    }

    if (isset($overrides['canceled_date'])) {
        $overrides['canceledDate'] = $overrides['canceled_date'];
        unset($overrides['canceled_date']);
    }

    return new SquareSubscription(array_merge([
        'id' => $subscriptionId,
        'status' => 'ACTIVE',
        'planVariationId' => $planId,
        'startDate' => now(config('app.timezone'))->format('Y-m-d'),
        'chargedThroughDate' => now(config('app.timezone'))->addMonth()->format('Y-m-d'),
    ], $overrides));
}

function makeSquarePayment(array $overrides = []): SquarePayment
{
    $paymentId = $overrides['id'] ?? 'pay_test_' . str()->random(8);
    unset($overrides['id']);

    if (array_key_exists('amount', $overrides)) {
        $overrides['totalMoney'] = new Money([
            'amount' => (int) $overrides['amount'],
            'currency' => 'JPY',
        ]);
        unset($overrides['amount']);
    }

    if (array_key_exists('created', $overrides)) {
        $overrides['createdAt'] = \Carbon\Carbon::createFromTimestamp(
            (int) $overrides['created'],
            config('app.timezone'),
        )->toIso8601String();
        unset($overrides['created']);
    }

    if (array_key_exists('paid', $overrides)) {
        $overrides['status'] = ($overrides['paid'] ? 'COMPLETED' : 'FAILED');
        unset($overrides['paid']);
    }

    if (isset($overrides['metadata'])) {
        $metadata = $overrides['metadata'];
        unset($overrides['metadata']);
        if (is_object($metadata)) {
            $metadata = (array) $metadata;
        }
        $overrides['note'] = json_encode($metadata, JSON_THROW_ON_ERROR);
    }

    return new SquarePayment(array_merge([
        'id' => $paymentId,
        'status' => 'COMPLETED',
    ], $overrides));
}

// ===== updateCard() メソッドのテストケース =====

test('4-3-1: 【カード更新】 課金状態配列を返し pm 情報を同期する', function () {
    $group = createBillingGroup([
        'square_customer_id' => 'cus_update_test',
        'plan' => GroupPlan::FREE,
    ]);
    $card = makeSquareCard('ccof_update_test', ['customerId' => 'cus_update_test']);

    $this->mock(SquareBillingClient::class, function ($mock) use ($card) {
        $mock->shouldReceive('addCustomerCardFromToken')
            ->once()
            ->with('cus_update_test', 'tok_test_update', null)
            ->andReturn($card);
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
    $group = createBillingGroup(['square_customer_id' => null]);
    $customer = makeSquareCustomer(['id' => 'cus_new_test']);
    $card = makeSquareCard('ccof_new_test', ['customerId' => 'cus_new_test']);

    $this->mock(SquareBillingClient::class, function ($mock) use ($group, $customer, $card) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with([
                'emailAddress' => $this->user->email,
                'note' => $this->user->name,
                'referenceId' => $group->id,
            ])
            ->andReturn($customer);
        $mock->shouldReceive('createCard')
            ->once()
            ->with('cus_new_test', 'tok_test_new')
            ->andReturn($card);
    });

    $status = billingService()->updateCard($group, $this->user, 'tok_test_new');

    $group->refresh();
    expect($group->square_customer_id)->toBe('cus_new_test')
        ->and($status['pmLastFour'])->toBe('4242');
});

test('4-3-3: 【カード更新】 Square API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup(['square_customer_id' => 'cus_fail_test']);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('addCustomerCardFromToken')
            ->once()
            ->andThrow(makeSquareApiException());
    });

    expect(fn() => billingService()->updateCard($group, $this->user, 'tok_fail'))
        ->toThrow(HttpException::class, 'カード情報の更新に失敗しました。');
});

// ===== deleteCard() メソッドのテストケース =====

test('4-3-4: 【カード削除】 登録カードを無効化し pm をクリアする', function () {
    $group = createBillingGroup([
        'square_customer_id' => 'cus_delete_test',
        'square_card_id' => 'ccof_delete_1',
        'pm_type' => 'Visa',
        'pm_last_four' => '4242',
        'pm_exp_month' => 12,
        'pm_exp_year' => 2028,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('disableCard')
            ->once()
            ->with('ccof_delete_1');
    });

    billingService()->deleteCard($group);

    $group->refresh();
    expect($group->square_card_id)->toBeNull()
        ->and($group->pm_type)->toBeNull()
        ->and($group->pm_last_four)->toBeNull()
        ->and($group->pm_exp_month)->toBeNull()
        ->and($group->pm_exp_year)->toBeNull();
});

test('4-3-5: 【カード削除】 Customer 未登録なら 422 を投げる', function () {
    $group = createBillingGroup(['square_customer_id' => null]);

    expect(fn() => billingService()->deleteCard($group))
        ->toThrow(HttpException::class, '課金情報が登録されていません。');
});

test('4-3-6: 【カード削除】 Square API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup([
        'square_customer_id' => 'cus_delete_fail',
        'square_card_id' => 'ccof_delete_fail',
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('disableCard')
            ->once()
            ->andThrow(makeSquareApiException());
    });

    expect(fn() => billingService()->deleteCard($group))
        ->toThrow(HttpException::class, 'カード情報の削除に失敗しました。');
});

// ===== createSubscription() メソッドのテストケース =====

test('4-3-7: 【サブスク開始】 Customer 未作成時にサブスクを開始し課金状態を返す', function () {
    $group = createBillingGroup(['square_customer_id' => null, 'ai_monthly_remaining' => 0]);
    $customer = makeSquareCustomer(['id' => 'cus_subscribe_new']);
    $card = makeSquareCard('ccof_subscribe_new', ['customerId' => 'cus_subscribe_new']);
    $periodEnd = now()->addMonth()->startOfDay()->startOfSecond();
    $squareSubscription = makeSquareSubscription([
        'id' => 'sub_subscribe_new',
        'current_period_end' => $periodEnd->timestamp,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($group, $customer, $card, $squareSubscription) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with([
                'emailAddress' => $this->user->email,
                'note' => $this->user->name,
                'referenceId' => $group->id,
            ])
            ->andReturn($customer);
        $mock->shouldReceive('createCard')
            ->once()
            ->with('cus_subscribe_new', 'tok_subscribe_new')
            ->andReturn($card);
        $mock->shouldReceive('createSubscription')
            ->once()
            ->with([
                'customerId' => 'cus_subscribe_new',
                'planVariationId' => config('billing.subscription_plan_ids.standard'),
                'cardId' => 'ccof_subscribe_new',
            ])
            ->andReturn($squareSubscription);
    });

    $status = billingService()->createSubscription(
        $group,
        $this->user,
        BillingSubscriptionType::STANDARD,
        'tok_subscribe_new',
    );

    $group->refresh();
    $subscription = $group->subscription();

    expect($group->square_customer_id)->toBe('cus_subscribe_new')
        ->and($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->pm_last_four)->toBe('4242')
        ->and($subscription)->not->toBeNull()
        ->and($subscription->square_subscription_id)->toBe('sub_subscribe_new')
        ->and($subscription->status)->toBe('active')
        ->and($status['plan'])->toBe('standard')
        ->and($status['isSubscribed'])->toBeTrue()
        ->and($status['subscriptionStatus'])->toBe('active');
});

test('4-3-8: 【サブスク開始】 既存 Customer でサブスクを開始する', function () {
    $group = createBillingGroup(['square_customer_id' => 'cus_subscribe_existing']);
    $card = makeSquareCard('ccof_subscribe_existing', ['customerId' => 'cus_subscribe_existing']);
    $squareSubscription = makeSquareSubscription(['id' => 'sub_subscribe_existing']);

    $this->mock(SquareBillingClient::class, function ($mock) use ($card, $squareSubscription) {
        $mock->shouldReceive('addCustomerCardFromToken')
            ->once()
            ->with('cus_subscribe_existing', 'tok_subscribe_existing', null)
            ->andReturn($card);
        $mock->shouldReceive('createSubscription')
            ->once()
            ->andReturn($squareSubscription);
    });

    billingService()->createSubscription(
        $group,
        $this->user,
        BillingSubscriptionType::STANDARD,
        'tok_subscribe_existing',
    );

    expect($group->fresh()->subscription()?->square_subscription_id)->toBe('sub_subscribe_existing');
});

test('4-3-9: 【サブスク開始】 current_period_end ありで月間残数をリセットする', function () {
    $periodEnd = now()->addMonth()->startOfDay()->startOfSecond();
    $group = createBillingGroup([
        'square_customer_id' => null,
        'ai_monthly_remaining' => 0,
    ]);
    $customer = makeSquareCustomer(['id' => 'cus_subscribe_renew']);
    $card = makeSquareCard('ccof_subscribe_renew', ['customerId' => 'cus_subscribe_renew']);
    $squareSubscription = makeSquareSubscription([
        'id' => 'sub_subscribe_renew',
        'current_period_end' => $periodEnd->timestamp,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($group, $customer, $card, $squareSubscription) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with([
                'emailAddress' => $this->user->email,
                'note' => $this->user->name,
                'referenceId' => $group->id,
            ])
            ->andReturn($customer);
        $mock->shouldReceive('createCard')
            ->once()
            ->with('cus_subscribe_renew', 'tok_subscribe_renew')
            ->andReturn($card);
        $mock->shouldReceive('createSubscription')->andReturn($squareSubscription);
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

test('4-3-11: 【サブスク開始】 Square API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup(['square_customer_id' => null]);
    $customer = makeSquareCustomer(['id' => 'cus_subscribe_fail']);
    $card = makeSquareCard('ccof_subscribe_fail', ['customerId' => 'cus_subscribe_fail']);

    $this->mock(SquareBillingClient::class, function ($mock) use ($customer, $card) {
        $mock->shouldReceive('createCustomer')->andReturn($customer);
        $mock->shouldReceive('createCard')->andReturn($card);
        $mock->shouldReceive('createSubscription')
            ->once()
            ->andThrow(makeSquareApiException());
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
    $periodEnd = now()->addDays(30)->startOfDay()->startOfSecond();
    $squareSubscriptionId = 'sub_cancel_test';
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD, 'square_customer_id' => 'cus_sub_cancel']);
    createSubscriptionRecord($group, [
        'square_subscription_id' => $squareSubscriptionId,
        'status' => 'active',
        'ends_at' => null,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($squareSubscriptionId, $periodEnd) {
        $mock->shouldReceive('cancelSubscription')
            ->once()
            ->with($squareSubscriptionId)
            ->andReturn(makeSquareSubscription([
                'id' => $squareSubscriptionId,
                'status' => 'canceled',
                'current_period_end' => $periodEnd->timestamp,
                'canceledDate' => $periodEnd->format('Y-m-d'),
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

test('4-3-14: 【サブスク解約】 Square API 失敗時は HttpException を投げる', function () {
    $squareSubscriptionId = 'sub_cancel_fail';
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'square_subscription_id' => $squareSubscriptionId,
        'status' => 'active',
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($squareSubscriptionId) {
        $mock->shouldReceive('cancelSubscription')
            ->once()
            ->with($squareSubscriptionId)
            ->andThrow(makeSquareApiException());
    });

    expect(fn() => billingService()->cancelSubscription($group->fresh()))
        ->toThrow(HttpException::class, 'サブスクリプションの解約に失敗しました。');
});

// ===== purchasePack() メソッドのテストケース =====

test('4-3-15: 【パック購入】 カードトークンでライトパックを購入し ai_pack_remaining を加算する', function () {
    $group = createBillingGroup(['square_customer_id' => null, 'ai_pack_remaining' => 5]);
    $customer = makeSquareCustomer(['id' => 'cus_pack_light']);
    $card = makeSquareCard('ccof_pack_light', ['customerId' => 'cus_pack_light']);

    $this->mock(SquareBillingClient::class, function ($mock) use ($group, $customer, $card) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with([
                'emailAddress' => $this->user->email,
                'note' => $this->user->name,
                'referenceId' => $group->id,
            ])
            ->andReturn($customer);
        $mock->shouldReceive('createCard')
            ->once()
            ->with('cus_pack_light', 'tok_pack_light')
            ->andReturn($card);
        $mock->shouldReceive('createPayment')
            ->once()
            ->with(Mockery::on(function (array $params) use ($group): bool {
                $note = json_decode($params['note'] ?? '', true);

                return $params['sourceId'] === 'ccof_pack_light'
                    && $params['customerId'] === 'cus_pack_light'
                    && $params['amount'] === 400
                    && $params['currency'] === 'JPY'
                    && is_array($note)
                    && ($note['type'] ?? null) === 'pack'
                    && ($note['pack_type'] ?? null) === 'light'
                    && ($note['group_id'] ?? null) === (string) $group->id;
            }))
            ->andReturn(makeSquarePayment(['id' => 'pay_pack_light']));
    });

    $status = billingService()->purchasePack(
        $group,
        $this->user,
        BillingPackType::LIGHT,
        'tok_pack_light',
    );

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(15)
        ->and($group->square_customer_id)->toBe('cus_pack_light')
        ->and($status['plan'])->toBe('free')
        ->and($status['isSubscribed'])->toBeFalse();
});

test('4-3-16: 【パック購入】 カードトークンでバリューパックを購入する', function () {
    $group = createBillingGroup(['square_customer_id' => null, 'ai_pack_remaining' => 0]);
    $customer = makeSquareCustomer(['id' => 'cus_pack_value']);
    $card = makeSquareCard('ccof_pack_value', ['customerId' => 'cus_pack_value']);

    $this->mock(SquareBillingClient::class, function ($mock) use ($group, $customer, $card) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with([
                'emailAddress' => $this->user->email,
                'note' => $this->user->name,
                'referenceId' => $group->id,
            ])
            ->andReturn($customer);
        $mock->shouldReceive('createCard')
            ->once()
            ->with('cus_pack_value', 'tok_pack_value')
            ->andReturn($card);
        $mock->shouldReceive('createPayment')
            ->once()
            ->with(Mockery::on(function (array $params): bool {
                $note = json_decode($params['note'] ?? '', true);

                return $params['amount'] === 800
                    && is_array($note)
                    && ($note['pack_type'] ?? null) === 'value';
            }))
            ->andReturn(makeSquarePayment(['id' => 'pay_pack_value']));
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
        'square_customer_id' => 'cus_pack_existing',
        'square_card_id' => 'ccof_pack_existing',
        'ai_pack_remaining' => 2,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldNotReceive('createCustomer');
        $mock->shouldNotReceive('addCustomerCardFromToken');
        $mock->shouldReceive('createPayment')
            ->once()
            ->with(Mockery::on(fn(array $params): bool => $params['customerId'] === 'cus_pack_existing'
                && $params['sourceId'] === 'ccof_pack_existing'))
            ->andReturn(makeSquarePayment(['id' => 'pay_pack_existing']));
    });

    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT);

    expect($group->fresh()->ai_pack_remaining)->toBe(12);
});

test('4-3-18: 【パック購入】 Customer 未登録かつトークンなしなら 422 を投げる', function () {
    $group = createBillingGroup(['square_customer_id' => null]);

    expect(fn() => billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT))
        ->toThrow(HttpException::class, '支払い方法が登録されていません。カード情報を登録してください。');
});

test('4-3-19: 【パック購入】 Square API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup([
        'square_customer_id' => 'cus_pack_charge_fail',
        'square_card_id' => 'ccof_pack_charge_fail',
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('createPayment')
            ->once()
            ->andThrow(makeSquareApiException());
    });

    expect(fn() => billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT))
        ->toThrow(HttpException::class, '買い切りパックの購入処理に失敗しました。');
});

test('4-3-20: 【パック購入】 空文字トークンは未指定として扱い登録済み Customer で課金する', function () {
    $group = createBillingGroup([
        'square_customer_id' => 'cus_pack_empty_token',
        'square_card_id' => 'ccof_pack_empty_token',
        'ai_pack_remaining' => 0,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldNotReceive('createCustomer');
        $mock->shouldNotReceive('addCustomerCardFromToken');
        $mock->shouldReceive('createPayment')
            ->once()
            ->andReturn(makeSquarePayment(['id' => 'pay_empty_token']));
    });

    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT, '');

    expect($group->fresh()->ai_pack_remaining)->toBe(10);
});

test('4-3-21: 【パック購入】 複数回購入で ai_pack_remaining が累積する', function () {
    $group = createBillingGroup([
        'square_customer_id' => 'cus_pack_accumulate',
        'square_card_id' => 'ccof_pack_accumulate',
        'ai_pack_remaining' => 5,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('createPayment')
            ->twice()
            ->andReturn(
                makeSquarePayment(['id' => 'pay_accumulate_1']),
                makeSquarePayment(['id' => 'pay_accumulate_2']),
            );
    });

    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT);
    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT);

    expect($group->fresh()->ai_pack_remaining)->toBe(25);
});

test('4-3-44: 【パック購入】 billing_histories にライトパックの description を保存する', function () {
    $group = createBillingGroup([
        'square_customer_id' => 'cus_pack_history_light',
        'square_card_id' => 'ccof_pack_history_light',
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('createPayment')
            ->once()
            ->andReturn(makeSquarePayment(['id' => 'pay_history_light']));
    });

    billingService()->purchasePack($group, $this->user, BillingPackType::LIGHT);

    $history = BillingHistory::where('square_payment_id', 'pay_history_light')->first();

    expect($history)->not->toBeNull()
        ->and($history->group_id)->toBe($group->id)
        ->and($history->type)->toBe('pack')
        ->and($history->description)->toBe('買い切りパック ライト');
});

test('4-3-45: 【パック購入】 billing_histories にバリューパックの description を保存する', function () {
    $group = createBillingGroup([
        'square_customer_id' => 'cus_pack_history_value',
        'square_card_id' => 'ccof_pack_history_value',
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('createPayment')
            ->once()
            ->andReturn(makeSquarePayment(['id' => 'pay_history_value']));
    });

    billingService()->purchasePack($group, $this->user, BillingPackType::VALUE);

    $history = BillingHistory::where('square_payment_id', 'pay_history_value')->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe('pack')
        ->and($history->description)->toBe('買い切りパック バリュー');
});

test('4-3-22: 【パック購入】 カード登録時の Square API 失敗時は HttpException を投げる', function () {
    $group = createBillingGroup(['square_customer_id' => null]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->andThrow(makeSquareApiException());
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
        'square_customer_id' => 'cus_pm_test',
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
    $squareSubscriptionId = 'sub_resume_test';
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'square_subscription_id' => $squareSubscriptionId,
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($squareSubscriptionId) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->with($squareSubscriptionId)
            ->andReturn(makeSquareSubscription([
                'id' => $squareSubscriptionId,
                'status' => 'active',
                'current_period_end' => now()->addMonth()->timestamp,
                'canceledDate' => null,
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

test('4-3-36: 【プラン変更予定取り消し】 Square API 失敗時は HttpException を投げる', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $squareSubscriptionId = 'sub_resume_fail';
    $group = createBillingGroup(['plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'square_subscription_id' => $squareSubscriptionId,
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($squareSubscriptionId) {
        $mock->shouldReceive('resumeSubscription')
            ->once()
            ->with($squareSubscriptionId)
            ->andThrow(makeSquareApiException());
    });

    expect(fn() => billingService()->resumeSubscription($group->fresh()))
        ->toThrow(HttpException::class, 'プラン変更予定の取り消しに失敗しました。');
});

// ===== getInvoices() メソッドのテストケース =====

test('4-3-37: 【請求履歴取得】 次回お支払い予定と過去 Payment 履歴を返す', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();
    $chargeCreated = now()->subMonth()->startOfSecond();
    $group = createBillingGroup(['square_customer_id' => 'cus_invoices', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'current_period_end' => $periodEnd,
    ]);

    BillingHistory::create([
        'group_id' => $group->id,
        'square_payment_id' => 'pay_test_paid',
        'type' => 'subscription',
        'description' => 'スタンダードプラン',
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($chargeCreated) {
        $mock->shouldReceive('listPayments')
            ->once()
            ->andReturn([
                makeSquarePayment([
                    'id' => 'pay_test_paid',
                    'paid' => true,
                    'amount' => 580,
                    'created' => $chargeCreated->timestamp,
                    'customerId' => 'cus_invoices',
                ]),
            ]);
        $mock->shouldReceive('isPaymentCompleted')->andReturn(true);
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
                'id' => 'pay_test_paid',
                'date' => $chargeCreated->toIso8601String(),
                'description' => 'スタンダードプラン',
                'total' => 580,
            ],
        ]);
});

test('4-3-38: 【請求履歴取得】 Customer 未登録なら空を返す', function () {
    $group = createBillingGroup(['square_customer_id' => null]);

    $result = billingService()->getInvoices($group);

    expect($result)->toBe([
        'upcomingInvoice' => null,
        'pastInvoices' => [],
    ]);
});

test('4-3-39: 【請求履歴取得】 解約猶予中（!isActive）は upcomingInvoice は null', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = createBillingGroup(['square_customer_id' => 'cus_grace', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'canceled',
        'ends_at' => $endsAt,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('listPayments')
            ->once()
            ->andReturn([
                makeSquarePayment([
                    'id' => 'pay_grace_paid',
                    'paid' => true,
                    'amount' => 400,
                    'created' => now()->subWeek()->timestamp,
                    'customerId' => 'cus_grace',
                ]),
            ]);
        $mock->shouldReceive('isPaymentCompleted')->andReturn(true);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['upcomingInvoice'])->toBeNull()
        ->and($result['pastInvoices'])->toHaveCount(1)
        ->and($result['pastInvoices'][0]['id'])->toBe('pay_grace_paid');
});

test('4-3-40: 【請求履歴取得】 未完了 Payment は pastInvoices に含めない', function () {
    $group = createBillingGroup(['square_customer_id' => 'cus_paid_filter', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, ['status' => 'active']);

    $paidAt = now()->subDays(3)->startOfSecond();

    $unpaid = makeSquarePayment([
        'id' => 'pay_unpaid',
        'paid' => false,
        'amount' => 580,
        'created' => now()->timestamp,
        'customerId' => 'cus_paid_filter',
    ]);
    $paid = makeSquarePayment([
        'id' => 'pay_paid_only',
        'paid' => true,
        'amount' => 580,
        'created' => $paidAt->timestamp,
        'customerId' => 'cus_paid_filter',
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($unpaid, $paid) {
        $mock->shouldReceive('listPayments')
            ->once()
            ->andReturn([$unpaid, $paid]);
        $mock->shouldReceive('isPaymentCompleted')
            ->with($unpaid)
            ->andReturn(false);
        $mock->shouldReceive('isPaymentCompleted')
            ->with($paid)
            ->andReturn(true);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['pastInvoices'])->toHaveCount(1)
        ->and($result['pastInvoices'][0]['id'])->toBe('pay_paid_only');
});

test('4-3-41: 【請求履歴取得】 Square 取得失敗時は upcoming と past を安全に返す', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();
    $group = createBillingGroup(['square_customer_id' => 'cus_list_fail', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'current_period_end' => $periodEnd,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('listPayments')
            ->once()
            ->andThrow(makeSquareApiException());
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['upcomingInvoice'])->not->toBeNull()
        ->and($result['upcomingInvoice']['date'])->toBe($periodEnd->toIso8601String())
        ->and($result['pastInvoices'])->toBe([]);
});

test('4-3-42: 【請求履歴取得】 Payment がない場合 pastInvoices は空配列', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();
    $group = createBillingGroup(['square_customer_id' => 'cus_no_charges', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, [
        'status' => 'active',
        'current_period_end' => $periodEnd,
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) {
        $mock->shouldReceive('listPayments')
            ->once()
            ->andReturn([]);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['upcomingInvoice'])->not->toBeNull()
        ->and($result['pastInvoices'])->toBe([]);
});

test('4-3-43: 【請求履歴取得】 パック購入 Payment の description が billing_histories から取得される', function () {
    $chargeCreated = now()->subDays(5)->startOfSecond();
    $group = createBillingGroup(['square_customer_id' => 'cus_pack_desc', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, ['status' => 'active']);

    BillingHistory::create([
        'group_id' => $group->id,
        'square_payment_id' => 'pay_pack_light',
        'type' => 'pack',
        'description' => '買い切りパック ライト',
    ]);

    $this->mock(SquareBillingClient::class, function ($mock) use ($chargeCreated) {
        $mock->shouldReceive('listPayments')
            ->once()
            ->andReturn([
                makeSquarePayment([
                    'id' => 'pay_pack_light',
                    'paid' => true,
                    'amount' => 400,
                    'created' => $chargeCreated->timestamp,
                    'customerId' => 'cus_pack_desc',
                ]),
            ]);
        $mock->shouldReceive('isPaymentCompleted')->andReturn(true);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['pastInvoices'])->toHaveCount(1)
        ->and($result['pastInvoices'][0]['description'])->toBe('買い切りパック ライト');
});

test('4-3-46: 【請求履歴取得】 billing_histories にない Payment は unknown の description を返す', function () {
    $chargeCreated = now()->subDays(2)->startOfSecond();
    $group = createBillingGroup(['square_customer_id' => 'cus_no_history', 'plan' => GroupPlan::STANDARD]);
    createSubscriptionRecord($group, ['status' => 'active']);

    $this->mock(SquareBillingClient::class, function ($mock) use ($chargeCreated) {
        $mock->shouldReceive('listPayments')
            ->once()
            ->andReturn([
                makeSquarePayment([
                    'id' => 'pay_legacy_no_history',
                    'paid' => true,
                    'amount' => 580,
                    'created' => $chargeCreated->timestamp,
                    'customerId' => 'cus_no_history',
                    'metadata' => [
                        'type' => 'pack',
                        'pack_type' => 'light',
                    ],
                ]),
            ]);
        $mock->shouldReceive('isPaymentCompleted')->andReturn(true);
    });

    $result = billingService()->getInvoices($group->fresh());

    expect($result['pastInvoices'])->toHaveCount(1)
        ->and($result['pastInvoices'][0]['description'])->toBe('その他のお支払い');
});
