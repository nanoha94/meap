<?php

use App\Enums\GroupPlan;
use App\Models\BillingHistory;
use App\Models\Group;
use App\Services\BillingWebhookService;
use App\Services\SquareBillingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

function mockSquareSubscriptionRetrieve(SquareSubscription $subscription, string $subscriptionId = 'sub_test_1'): void
{
    test()->mock(SquareBillingClient::class, function ($mock) use ($subscription, $subscriptionId) {
        $mock->shouldReceive('retrieveSubscription')
            ->with($subscriptionId)
            ->andReturn($subscription);
    });

    test()->service = app(BillingWebhookService::class);
}

test('4-2-1: 【定期更新】 invoice.payment_made でプラン更新と利用回数リセットを行う', function () {
    $group = makeSquareBillableGroup();
    $chargedThrough = now()->addDays(30)->format('Y-m-d');

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk([
        'charged_through_date' => $chargedThrough,
    ]));

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope());

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(GroupPlan::STANDARD->monthlyLimit())
        ->and($group->ai_usage_reset_at?->format('Y-m-d'))->toBe($chargedThrough);
});

test('4-2-2: 【定期更新】 顧客不在はスキップする', function () {
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

test('4-2-3: 【定期更新】 同一 event ID の再送は二重処理しない', function () {
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

test('4-2-10: 【定期更新】 invoice.payment_made で billing_histories にサブスク description を保存する', function () {
    makeSquareBillableGroup();

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk());

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope([
        'payment_ids' => ['pay_sub_invoice_1'],
    ]));

    $history = BillingHistory::where('square_payment_id', 'pay_sub_invoice_1')->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe('subscription')
        ->and($history->description)->toBe('スタンダードプラン');
});

test('4-2-11: 【定期更新】 同一 Payment ID の再処理で billing_histories が重複しない', function () {
    makeSquareBillableGroup();

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk());

    $invoiceOverrides = ['payment_ids' => ['pay_sub_idempotent']];

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope($invoiceOverrides, 'evt_billing_1'));
    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope($invoiceOverrides, 'evt_billing_2'));

    expect(BillingHistory::where('square_payment_id', 'pay_sub_idempotent')->count())->toBe(1);
});

test('4-2-12: 【定期更新】 不明 plan variation では billing_histories を書かない', function () {
    makeSquareBillableGroup();

    mockSquareSubscriptionRetrieve(makeSquareSubscriptionSdk([
        'plan_variation_id' => 'pln_unknown',
    ]));

    $this->service->handleInvoicePaymentMade(makeSquareInvoicePaymentEnvelope([
        'payment_ids' => ['pay_sub_unknown_plan'],
    ]));

    expect(BillingHistory::where('square_payment_id', 'pay_sub_unknown_plan')->exists())->toBeFalse();
});

test('4-2-4: 【サブスク同期】 ACTIVE で STANDARD プランを付与する', function () {
    $group = makeSquareBillableGroup();
    $subscription = makeSquareSubscriptionObject(['status' => 'ACTIVE']);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_changed_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::STANDARD);
});

test('4-2-5: 【サブスク同期】 CANCELED（期間終了後）で FREE に戻す', function () {
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

test('4-2-6: 【サブスク同期】 PAUSED で FREE に戻す', function () {
    $group = makeSquareBillableGroup(['plan' => GroupPlan::STANDARD]);
    $subscription = makeSquareSubscriptionObject(['status' => 'PAUSED']);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_paused_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-7: 【サブスク同期】 不明な plan variation ID ではプラン更新しない', function () {
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

test('4-2-8: 【支払い失敗】 payment.updated（FAILED）は例外なく処理できる', function () {
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

test('4-2-9: 【サブスク同期】 PENDING（未来開始・未課金）ではプラン付与しない', function () {
    $group = makeSquareBillableGroup();
    $subscription = makeSquareSubscriptionObject(['status' => 'PENDING']);

    $this->service->handleSubscriptionUpdated(
        makeSquareSubscriptionWebhookEnvelope($subscription, 'evt_pending_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});
