<?php

use App\Enums\GroupPlan;
use App\Models\BillingHistory;
use App\Models\Group;
use App\Models\Subscription;
use App\Services\BillingWebhookService;
use App\Services\SquareBillingClient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Square\Exceptions\SquareApiException;
use Square\Types\Money;
use Square\Types\Payment;
use Square\Types\Subscription as SquareSubscription;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'billing.subscription_plan_ids.standard' => 'pln_standard_test',
    ]);

    Cache::flush();

    $this->standardPlanVariationId = 'pln_standard_test';
    $this->squareCustomerId = 'cus_test_123';
    $this->service = app(BillingWebhookService::class);
});

function makeSquareBillableGroup(array $attributes = []): Group
{
    return Group::factory()->create(array_merge([
        'square_customer_id' => test()->squareCustomerId,
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => GroupPlan::FREE->monthlyLimit(),
        'ai_pack_remaining' => 0,
        'ai_usage_reset_at' => now()->addMonth(),
    ], $attributes));
}

function makeSquareSubscriptionObject(array $overrides = []): array
{
    $chargedThrough = now()->addDays(30)->format('Y-m-d');

    return array_replace_recursive([
        'id' => 'sub_test_1',
        'customer_id' => test()->squareCustomerId,
        'status' => 'ACTIVE',
        'plan_variation_id' => test()->standardPlanVariationId,
        'charged_through_date' => $chargedThrough,
    ], $overrides);
}

function makeSquareSubscriptionSdk(array $overrides = []): SquareSubscription
{
    if (isset($overrides['customer_id'])) {
        $overrides['customerId'] = $overrides['customer_id'];
        unset($overrides['customer_id']);
    }

    if (isset($overrides['plan_variation_id'])) {
        $overrides['planVariationId'] = $overrides['plan_variation_id'];
        unset($overrides['plan_variation_id']);
    }

    if (isset($overrides['charged_through_date'])) {
        $overrides['chargedThroughDate'] = $overrides['charged_through_date'];
        unset($overrides['charged_through_date']);
    }

    if (isset($overrides['status'])) {
        $overrides['status'] = strtoupper((string) $overrides['status']);
    }

    return new SquareSubscription(array_merge([
        'id' => 'sub_test_1',
        'customerId' => test()->squareCustomerId,
        'status' => 'ACTIVE',
        'planVariationId' => test()->standardPlanVariationId,
        'chargedThroughDate' => now()->addDays(30)->format('Y-m-d'),
    ], $overrides));
}

function makeSquareSubscriptionWebhookEnvelope(array $subscription, ?string $eventId = 'evt_sub_1'): array
{
    return [
        'event_id' => $eventId,
        'type' => 'subscription.updated',
        'data' => [
            'type' => 'subscription',
            'id' => $subscription['id'] ?? 'sub_test_1',
            'object' => [
                'subscription' => $subscription,
            ],
        ],
    ];
}

function makeSquareInvoicePaymentEnvelope(
    array $invoiceOverrides = [],
    ?string $eventId = 'evt_invoice_1',
): array {
    $invoice = array_replace_recursive([
        'id' => 'inv_test_1',
        'subscription_id' => 'sub_test_1',
        'primary_recipient' => [
            'customer_id' => test()->squareCustomerId,
        ],
        'status' => 'PAID',
    ], $invoiceOverrides);

    return [
        'event_id' => $eventId,
        'type' => 'invoice.payment_made',
        'data' => [
            'type' => 'invoice',
            'id' => $invoice['id'],
            'object' => [
                'invoice' => $invoice,
            ],
        ],
    ];
}

function mockSquareSubscriptionRetrieve(
    SquareSubscription $subscription,
    string $subscriptionId = 'sub_test_1',
    ?Closure $configure = null,
): void {
    test()->partialMock(SquareBillingClient::class, function ($mock) use ($subscription, $subscriptionId, $configure) {
        $mock->shouldReceive('retrieveSubscription')
            ->with($subscriptionId)
            ->andReturn($subscription);

        if ($configure !== null) {
            $configure($mock);
        }
    });

    test()->service = app(BillingWebhookService::class);
}

function makeWebhookSquarePayment(string $paymentId, int $amount, Carbon $paidAt): Payment
{
    return new Payment([
        'id' => $paymentId,
        'status' => 'COMPLETED',
        'createdAt' => $paidAt->toIso8601String(),
        'totalMoney' => new Money([
            'amount' => $amount,
            'currency' => 'JPY',
        ]),
    ]);
}

test('4-2-1: 【定期更新】 invoice.payment_made でプラン更新と利用回数リセットを行う', function () {
    $group = makeSquareBillableGroup();
    $chargedThrough = now()->addDays(30)->format('Y-m-d');

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk([
        'charged_through_date' => $chargedThrough,
    ]));

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope());

    $group->refresh();

    $subscription = Subscription::where('group_id', $group->id)->first();

    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(GroupPlan::STANDARD->monthlyLimit())
        ->and($group->ai_usage_reset_at?->format('Y-m-d'))->toBe($chargedThrough)
        ->and($subscription)->not->toBeNull()
        ->and($subscription->square_subscription_id)->toBe('sub_test_1')
        ->and($subscription->status)->toBe('active');
});

test('4-2-2: 【定期更新】 invoice.payment_made で subscription 行を保存してからプランを更新する', function () {
    $group = makeSquareBillableGroup(['plan' => GroupPlan::FREE]);
    Subscription::query()->create([
        'group_id' => $group->id,
        'square_subscription_id' => 'sub_old_invoice',
        'square_plan_variation_id' => $this->standardPlanVariationId,
        'status' => 'canceled',
        'current_period_end' => null,
    ]);

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk([
        'id' => 'sub_new_invoice',
        'status' => 'ACTIVE',
    ]), 'sub_new_invoice');

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope([
        'subscription_id' => 'sub_new_invoice',
    ]));

    $group->refresh();
    $created = Subscription::query()->where('square_subscription_id', 'sub_new_invoice')->first();

    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($created)->not->toBeNull()
        ->and($created->status)->toBe('active')
        ->and($created->group_id)->toBe($group->id);
});

test('4-2-3: 【定期更新】 顧客不在はスキップする', function () {
    $group = makeSquareBillableGroup(['square_customer_id' => 'cus_other']);
    $beforePlan = $group->plan;
    $beforeRemaining = $group->ai_monthly_remaining;

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk([
        'customer_id' => 'cus_unknown',
    ]));

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope());

    $group->refresh();

    expect($group->plan)->toBe($beforePlan)
        ->and($group->ai_monthly_remaining)->toBe($beforeRemaining);
});

test('4-2-4: 【定期更新】 同一 event ID の再送は二重処理しない', function () {
    $group = makeSquareBillableGroup();

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk());

    $payload = makeSquareInvoicePaymentEnvelope([], 'evt_duplicate');

    $this->service->handleInvoicePaymentMade($payload);
    $group->refresh();
    $remainingAfterFirst = $group->ai_monthly_remaining;

    $group->forceFill(['ai_monthly_remaining' => 0])->save();

    $this->service->handleInvoicePaymentMade($payload);
    $group->refresh();

    expect($group->ai_monthly_remaining)->toBe(0)
        ->and($remainingAfterFirst)->toBe(GroupPlan::STANDARD->monthlyLimit());
});

test('4-2-5: 【定期更新】 invoice.payment_made で billing_histories にサブスク description を保存する', function () {
    makeSquareBillableGroup();
    $paidAt = now()->subMinute()->startOfSecond();

    mockSquareSubscriptionRetrieve(
        makeSquareSubscriptionSdk(),
        'sub_test_1',
        function ($mock) use ($paidAt) {
            $mock->shouldReceive('retrievePayment')
                ->once()
                ->with('pay_sub_invoice_1')
                ->andReturn(makeWebhookSquarePayment('pay_sub_invoice_1', 580, $paidAt));
        },
    );

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope([
        'payment_ids' => ['pay_sub_invoice_1'],
    ]));

    $history = BillingHistory::where('square_payment_id', 'pay_sub_invoice_1')->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe('subscription')
        ->and($history->description)->toBe('スタンダードプラン')
        ->and($history->amount)->toBe(580)
        ->and($history->paid_at?->equalTo($paidAt))->toBeTrue();
});

test('4-2-6: 【定期更新】 同一 Payment ID の再処理で billing_histories が重複しない', function () {
    makeSquareBillableGroup();
    $paidAt = now()->subMinute()->startOfSecond();

    mockSquareSubscriptionRetrieve(
        makeSquareSubscriptionSdk(),
        'sub_test_1',
        function ($mock) use ($paidAt) {
            $mock->shouldReceive('retrievePayment')
                ->twice()
                ->with('pay_sub_idempotent')
                ->andReturn(makeWebhookSquarePayment('pay_sub_idempotent', 580, $paidAt));
        },
    );

    $invoiceOverrides = ['payment_ids' => ['pay_sub_idempotent']];

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope($invoiceOverrides, 'evt_billing_1'));
    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope($invoiceOverrides, 'evt_billing_2'));

    $histories = BillingHistory::where('square_payment_id', 'pay_sub_idempotent')->get();

    expect($histories)->toHaveCount(1)
        ->and($histories->first()->amount)->toBe(580)
        ->and($histories->first()->paid_at?->equalTo($paidAt))->toBeTrue();
});

test('4-2-7: 【定期更新】 Payment の金額と決済日時で既存 billing_histories を上書きする', function () {
    $group = makeSquareBillableGroup();
    $paidAt = now()->subMinute()->startOfSecond();

    BillingHistory::create([
        'group_id' => $group->id,
        'square_payment_id' => 'pay_sub_overwrite',
        'type' => 'subscription',
        'description' => '仮の説明',
        'amount' => 100,
        'paid_at' => now()->subDays(2),
    ]);

    mockSquareSubscriptionRetrieve(
        makeSquareSubscriptionSdk(),
        'sub_test_1',
        function ($mock) use ($paidAt) {
            $mock->shouldReceive('retrievePayment')
                ->once()
                ->with('pay_sub_overwrite')
                ->andReturn(makeWebhookSquarePayment('pay_sub_overwrite', 580, $paidAt));
        },
    );

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope([
        'payment_ids' => ['pay_sub_overwrite'],
    ]));

    $histories = BillingHistory::where('square_payment_id', 'pay_sub_overwrite')->get();

    expect($histories)->toHaveCount(1)
        ->and($histories->first()->description)->toBe('スタンダードプラン')
        ->and($histories->first()->amount)->toBe(580)
        ->and($histories->first()->paid_at?->equalTo($paidAt))->toBeTrue();
});

test('4-2-8: 【定期更新】 Payment 取得失敗時は Invoice の金額と更新日時を保存する', function () {
    makeSquareBillableGroup();
    $updatedAt = now()->subMinutes(5)->startOfSecond();

    mockSquareSubscriptionRetrieve(
        makeSquareSubscriptionSdk(),
        'sub_test_1',
        function ($mock) {
            $mock->shouldReceive('retrievePayment')
                ->once()
                ->with('pay_sub_invoice_fallback')
                ->andThrow(new SquareApiException('Square API error', 502, null));
        },
    );

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope([
        'payment_ids' => ['pay_sub_invoice_fallback'],
        'updated_at' => $updatedAt->toIso8601String(),
        'payment_requests' => [
            [
                'total_completed_amount_money' => ['amount' => 0, 'currency' => 'JPY'],
                'computed_amount_money' => ['amount' => 480, 'currency' => 'JPY'],
            ],
        ],
    ]));

    $history = BillingHistory::where('square_payment_id', 'pay_sub_invoice_fallback')->first();

    expect($history)->not->toBeNull()
        ->and($history->description)->toBe('スタンダードプラン')
        ->and($history->amount)->toBe(480)
        ->and($history->paid_at?->equalTo($updatedAt))->toBeTrue();
});

test('4-2-9: 【定期更新】 金額が取れないときは既存の amount と paid_at を残す', function () {
    $group = makeSquareBillableGroup();
    $paidAt = now()->subDays(3)->startOfSecond();

    BillingHistory::create([
        'group_id' => $group->id,
        'square_payment_id' => 'pay_sub_keep',
        'type' => 'subscription',
        'description' => '仮の説明',
        'amount' => 580,
        'paid_at' => $paidAt,
    ]);

    mockSquareSubscriptionRetrieve(
        makeSquareSubscriptionSdk(),
        'sub_test_1',
        function ($mock) {
            $mock->shouldReceive('retrievePayment')
                ->once()
                ->with('pay_sub_keep')
                ->andThrow(new SquareApiException('Square API error', 502, null));
        },
    );

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope([
        'payment_ids' => ['pay_sub_keep'],
    ]));

    $histories = BillingHistory::where('square_payment_id', 'pay_sub_keep')->get();

    expect($histories)->toHaveCount(1)
        ->and($histories->first()->description)->toBe('スタンダードプラン')
        ->and($histories->first()->amount)->toBe(580)
        ->and($histories->first()->paid_at?->equalTo($paidAt))->toBeTrue();
});

test('4-2-10: 【定期更新】 不明 plan variation では billing_histories を書かない', function () {
    makeSquareBillableGroup();

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk([
        'plan_variation_id' => 'pln_unknown',
    ]));

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope([
        'payment_ids' => ['pay_sub_unknown_plan'],
    ]));

    expect(BillingHistory::where('square_payment_id', 'pay_sub_unknown_plan')->exists())->toBeFalse();
});

test('4-2-11: 【サブスク同期】 ACTIVE で STANDARD プランを付与する', function () {
    $group = makeSquareBillableGroup();
    $subscription = makeSquareSubscriptionObject(['status' => 'ACTIVE']);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_changed_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::STANDARD);
});

test('4-2-12: 【サブスク同期】 CANCELED（期間終了後）で FREE に戻す', function () {
    $group = makeSquareBillableGroup(['plan' => GroupPlan::STANDARD]);
    $subscription = makeSquareSubscriptionObject([
        'status' => 'CANCELED',
        'charged_through_date' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_canceled_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-13: 【サブスク同期】 PAUSED で FREE に戻す', function () {
    $group = makeSquareBillableGroup(['plan' => GroupPlan::STANDARD]);
    $subscription = makeSquareSubscriptionObject(['status' => 'PAUSED']);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_paused_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-14: 【サブスク同期】 不明な plan variation ID ではプラン更新しない', function () {
    $group = makeSquareBillableGroup();
    $subscription = makeSquareSubscriptionObject([
        'plan_variation_id' => 'pln_unknown',
    ]);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_unknown_plan'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-15: 【支払い失敗】 payment.updated（FAILED）は例外なく処理できる', function () {
    $group = makeSquareBillableGroup();
    $beforePlan = $group->plan;

    $this->service->handlePaymentUpdated([
        'event_id' => 'evt_payment_failed_1',
        'type' => 'payment.updated',
        'data' => [
            'type' => 'payment',
            'object' => [
                'payment' => [
                    'id' => 'pay_failed',
                    'status' => 'FAILED',
                ],
            ],
        ],
    ]);

    $group->refresh();

    expect($group->plan)->toBe($beforePlan);
});

test('4-2-16: 【サブスク同期】 PENDING（未来開始・未課金）ではプラン付与しない', function () {
    $group = makeSquareBillableGroup();
    $subscription = makeSquareSubscriptionObject(['status' => 'PENDING']);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_pending_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-17: 【サブスク同期】 DEACTIVATED で subscriptions.status を更新する', function () {
    $group = makeSquareBillableGroup(['plan' => GroupPlan::STANDARD]);
    Subscription::query()->create([
        'group_id' => $group->id,
        'square_subscription_id' => 'sub_test_1',
        'square_plan_variation_id' => $this->standardPlanVariationId,
        'status' => 'active',
        'current_period_end' => null,
    ]);

    $subscription = makeSquareSubscriptionObject(['status' => 'DEACTIVATED']);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_deactivated_1'),
    );

    $local = Subscription::query()->where('square_subscription_id', 'sub_test_1')->first();

    expect($local)->not->toBeNull()
        ->and($local->status)->toBe('canceled');
});

test('4-2-18: 【サブスク同期】 ACTIVE（charged_through_date 付き）で current_period_end を更新する', function () {
    $group = makeSquareBillableGroup();
    Subscription::query()->create([
        'group_id' => $group->id,
        'square_subscription_id' => 'sub_test_1',
        'status' => 'active',
        'current_period_end' => null,
    ]);

    $chargedThrough = now()->addDays(30)->format('Y-m-d');
    $subscription = makeSquareSubscriptionObject([
        'status' => 'ACTIVE',
        'charged_through_date' => $chargedThrough,
    ]);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_active_period_1'),
    );

    $local = Subscription::query()->where('square_subscription_id', 'sub_test_1')->first();

    expect($local->current_period_end?->format('Y-m-d'))->toBe($chargedThrough);
});

test('4-2-19: 【サブスク同期】 status 欠落ペイロードでは subscriptions も plan も更新しない', function () {
    $group = makeSquareBillableGroup(['plan' => GroupPlan::STANDARD]);
    Subscription::query()->create([
        'group_id' => $group->id,
        'square_subscription_id' => 'sub_test_1',
        'square_plan_variation_id' => $this->standardPlanVariationId,
        'status' => 'active',
        'current_period_end' => null,
    ]);

    $subscription = makeSquareSubscriptionObject(['status' => null]);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_status_missing_1'),
    );

    $group->refresh();
    $local = Subscription::query()->where('square_subscription_id', 'sub_test_1')->first();

    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($local->status)->toBe('active');
});

test('4-2-20: 【サブスク同期】 charged_through_date 欠落時 canceled_date で current_period_end を補完する', function () {
    $group = makeSquareBillableGroup();
    Subscription::query()->create([
        'group_id' => $group->id,
        'square_subscription_id' => 'sub_test_1',
        'status' => 'active',
        'current_period_end' => null,
    ]);

    $canceledDate = now()->addDays(30)->format('Y-m-d');
    $subscription = makeSquareSubscriptionObject([
        'status' => 'ACTIVE',
        'charged_through_date' => null,
        'canceled_date' => $canceledDate,
    ]);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_canceled_date_fallback_1'),
    );

    $local = Subscription::query()->where('square_subscription_id', 'sub_test_1')->first();

    expect($local->current_period_end?->format('Y-m-d'))->toBe($canceledDate);
});

test('4-2-21: 【サブスク同期】 subscription id 欠落時はプランを更新しない', function () {
    $group = makeSquareBillableGroup(['plan' => GroupPlan::FREE]);
    $subscription = makeSquareSubscriptionObject([
        'id' => '',
        'status' => 'ACTIVE',
    ]);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_missing_subscription_id'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE)
        ->and(Subscription::query()->where('group_id', $group->id)->count())->toBe(0);
});
