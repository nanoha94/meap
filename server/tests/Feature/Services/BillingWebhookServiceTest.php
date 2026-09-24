<?php

use App\Enums\GroupPlan;
use App\Models\Group;
use App\Services\BillingWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'billing.subscription_plan_ids.standard' => 'pln_standard_test',
    ]);

    Cache::flush();

    $this->service = app(BillingWebhookService::class);
    $this->standardPlanId = 'pln_standard_test';
    $this->payjpCustomerId = 'cus_test_123';
});

function makePayjpBillableGroup(array $attributes = []): Group
{
    return Group::factory()->create(array_merge([
        'payjp_customer_id' => test()->payjpCustomerId,
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => GroupPlan::FREE->monthlyLimit(),
        'ai_pack_remaining' => 0,
        'ai_usage_reset_at' => now()->addMonth(),
    ], $attributes));
}

function makePayjpSubscriptionPayload(array $overrides = []): array
{
    $periodEnd = now()->addDays(30)->timestamp;

    return array_replace_recursive([
        'id' => 'sub_test_1',
        'customer' => test()->payjpCustomerId,
        'status' => 'active',
        'plan' => ['id' => test()->standardPlanId],
        'current_period_end' => $periodEnd,
        'metadata' => ['group_id' => null],
    ], $overrides);
}

function makePayjpWebhookEnvelope(array $subscription, ?string $eventId = 'evt_sub_1'): array
{
    return [
        'id' => $eventId,
        'data' => $subscription,
    ];
}

test('4-2-1: 【定期更新】 subscription.renewed でプラン更新と利用回数リセットを行う', function () {
    $group = makePayjpBillableGroup();
    $periodEnd = now()->addDays(30)->timestamp;
    $subscription = makePayjpSubscriptionPayload([
        'current_period_end' => $periodEnd,
    ]);

    $this->service->handleSubscriptionRenewed(
        makePayjpWebhookEnvelope($subscription),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(GroupPlan::STANDARD->monthlyLimit())
        ->and($group->ai_usage_reset_at?->timestamp)->toBe($periodEnd);
});

test('4-2-2: 【定期更新】 顧客不在はスキップする', function () {
    $group = makePayjpBillableGroup(['payjp_customer_id' => 'cus_other']);
    $beforePlan = $group->plan;
    $beforeRemaining = $group->ai_monthly_remaining;

    $subscription = makePayjpSubscriptionPayload([
        'customer' => 'cus_unknown',
    ]);

    $this->service->handleSubscriptionRenewed(
        makePayjpWebhookEnvelope($subscription),
    );

    $group->refresh();

    expect($group->plan)->toBe($beforePlan)
        ->and($group->ai_monthly_remaining)->toBe($beforeRemaining);
});

test('4-2-3: 【定期更新】 同一 event ID の再送は二重処理しない', function () {
    $group = makePayjpBillableGroup();
    $subscription = makePayjpSubscriptionPayload();
    $payload = makePayjpWebhookEnvelope($subscription, 'evt_duplicate');

    $this->service->handleSubscriptionRenewed($payload);
    $group->refresh();
    $remainingAfterFirst = $group->ai_monthly_remaining;

    $group->forceFill(['ai_monthly_remaining' => 0])->save();

    $this->service->handleSubscriptionRenewed($payload);
    $group->refresh();

    expect($group->ai_monthly_remaining)->toBe(0)
        ->and($remainingAfterFirst)->toBe(GroupPlan::STANDARD->monthlyLimit());
});

test('4-2-4: 【サブスク同期】 active で STANDARD プランを付与する', function () {
    $group = makePayjpBillableGroup();
    $subscription = makePayjpSubscriptionPayload(['status' => 'active']);

    $this->service->handleSubscriptionChanged(
        makePayjpWebhookEnvelope($subscription, 'evt_changed_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::STANDARD);
});

test('4-2-5: 【サブスク同期】 canceled（期間終了後）で FREE に戻す', function () {
    $group = makePayjpBillableGroup(['plan' => GroupPlan::STANDARD]);
    $subscription = makePayjpSubscriptionPayload([
        'status' => 'canceled',
        'current_period_end' => now()->subDay()->timestamp,
    ]);

    $this->service->handleSubscriptionDeleted(
        makePayjpWebhookEnvelope($subscription, 'evt_deleted_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-6: 【サブスク同期】 paused で FREE に戻す', function () {
    $group = makePayjpBillableGroup(['plan' => GroupPlan::STANDARD]);
    $subscription = makePayjpSubscriptionPayload(['status' => 'paused']);

    $this->service->handleSubscriptionPaused(
        makePayjpWebhookEnvelope($subscription, 'evt_paused_1'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-7: 【サブスク同期】 不明な plan ID ではプラン更新しない', function () {
    $group = makePayjpBillableGroup();
    $subscription = makePayjpSubscriptionPayload([
        'plan' => ['id' => 'pln_unknown'],
    ]);

    $this->service->handleSubscriptionChanged(
        makePayjpWebhookEnvelope($subscription, 'evt_unknown_plan'),
    );

    $group->refresh();

    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-8: 【支払い失敗】 charge.failed は例外なく処理できる', function () {
    $group = makePayjpBillableGroup();
    $beforePlan = $group->plan;

    $this->service->handleChargeFailed([
        'id' => 'evt_charge_failed_1',
        'data' => ['id' => 'ch_failed'],
    ]);

    $group->refresh();

    expect($group->plan)->toBe($beforePlan);
});
