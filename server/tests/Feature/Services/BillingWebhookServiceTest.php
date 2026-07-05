<?php

use App\Enums\GroupPlan;
use App\Models\Group;
use App\Services\BillingWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'billing.price_ids.subscription_standard' => 'price_test_standard',
        'billing.subscription_type' => 'default',
    ]);

    Cache::flush();

    $this->service = app(BillingWebhookService::class);
    $this->standardPriceId = 'price_test_standard';
    $this->stripeCustomerId = 'cus_test_123';
});

function makeBillableGroup(array $attributes = []): Group
{
    return Group::factory()->create(array_merge([
        'stripe_id' => test()->stripeCustomerId,
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => GroupPlan::FREE->monthlyLimit(),
        'ai_pack_remaining' => 0,
        'ai_usage_reset_at' => now()->addMonth(),
    ], $attributes));
}

function makeInvoicePaidPayload(array $invoiceOverrides = [], ?string $eventId = 'evt_invoice_1'): array
{
    $periodEnd = now()->addDays(30)->timestamp;

    $invoice = array_replace_recursive([
        'subscription' => 'sub_test_1',
        'customer' => test()->stripeCustomerId,
        'lines' => [
            'data' => [
                [
                    'price' => ['id' => test()->standardPriceId],
                    'period' => ['end' => $periodEnd],
                ],
            ],
        ],
    ], $invoiceOverrides);

    return [
        'id' => $eventId,
        'data' => ['object' => $invoice],
    ];
}

function makeSubscriptionPayload(
    string $status,
    array $overrides = [],
): array {
    $subscription = array_replace_recursive([
        'status' => $status,
        'metadata' => ['type' => 'default'],
        'items' => [
            'data' => [
                ['price' => ['id' => test()->standardPriceId]],
            ],
        ],
    ], $overrides);

    return $subscription;
}

function makeCheckoutSessionPayload(
    Group $group,
    int $credits = 10,
    array $sessionOverrides = [],
    ?string $eventId = 'evt_checkout_1',
): array {
    $session = array_replace_recursive([
        'metadata' => [
            'type' => 'pack',
            'group_id' => $group->id,
            'credits' => (string) $credits,
        ],
        'payment_status' => 'paid',
    ], $sessionOverrides);

    return [
        'id' => $eventId,
        'data' => ['object' => $session],
    ];
}

function assertGroupUsageUnchanged(Group $group, Group $before): void
{
    $group->refresh();

    expect($group->plan)->toBe($before->plan)
        ->and($group->ai_monthly_remaining)->toBe($before->ai_monthly_remaining)
        ->and($group->ai_usage_reset_at?->timestamp)
        ->toBe($before->ai_usage_reset_at?->timestamp);
}

function expectSameTimestamp(?\Carbon\Carbon $actual, int $expectedUnixTimestamp): void
{
    expect($actual?->timestamp)->toBe($expectedUnixTimestamp);
}

// ===== handleInvoicePaid() メソッドのテストケース =====

test('4-2-1: 【請求成功】 サブスク請求成功時にプラン更新と利用回数リセットを行う', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
    ]);
    $periodEnd = now()->addDays(30)->startOfSecond();

    $payload = makeInvoicePaidPayload([
        'lines' => [
            'data' => [
                [
                    'price' => ['id' => $this->standardPriceId],
                    'period' => ['end' => $periodEnd->timestamp],
                ],
            ],
        ],
    ]);

    $this->service->handleInvoicePaid($payload);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(30);
    expectSameTimestamp($group->ai_usage_reset_at, $periodEnd->timestamp);
});

test('4-2-2: 【請求成功】 既存 STANDARD プランでも請求周期更新で月間枠をリセットする', function () {
    $oldPeriodEnd = now()->addDays(5)->startOfSecond();
    $newPeriodEnd = now()->addDays(35)->startOfSecond();

    $group = makeBillableGroup([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 5,
        'ai_usage_reset_at' => $oldPeriodEnd,
    ]);

    $payload = makeInvoicePaidPayload([
        'lines' => [
            'data' => [
                [
                    'price' => ['id' => $this->standardPriceId],
                    'period' => ['end' => $newPeriodEnd->timestamp],
                ],
            ],
        ],
    ]);

    $this->service->handleInvoicePaid($payload);

    $group->refresh();
    expect($group->ai_monthly_remaining)->toBe(30);
    expectSameTimestamp($group->ai_usage_reset_at, $newPeriodEnd->timestamp);
});

test('4-2-3: 【請求成功】 subscription 無しの invoice はスキップする', function () {
    $group = makeBillableGroup();
    $before = $group->fresh();

    $payload = makeInvoicePaidPayload(['subscription' => null]);
    unset($payload['data']['object']['subscription']);

    $this->service->handleInvoicePaid($payload);

    assertGroupUsageUnchanged($group, $before);
});

test('4-2-4: 【請求成功】 顧客不在（stripe_id 不一致）はスキップする', function () {
    $group = makeBillableGroup();
    $before = $group->fresh();

    $payload = makeInvoicePaidPayload(['customer' => 'cus_unknown']);

    $this->service->handleInvoicePaid($payload);

    assertGroupUsageUnchanged($group, $before);
});

test('4-2-5: 【請求成功】 同一 event ID の再送は二重処理しない', function () {
    $firstPeriodEnd = now()->addDays(20)->startOfSecond();
    $secondPeriodEnd = now()->addDays(50)->startOfSecond();

    $group = makeBillableGroup([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 5,
        'ai_usage_reset_at' => now()->subDay(),
    ]);

    $payload = makeInvoicePaidPayload([
        'lines' => [
            'data' => [
                [
                    'price' => ['id' => $this->standardPriceId],
                    'period' => ['end' => $firstPeriodEnd->timestamp],
                ],
            ],
        ],
    ], 'evt_duplicate_invoice');

    $this->service->handleInvoicePaid($payload);
    $group->refresh();

    expect($group->ai_monthly_remaining)->toBe(30);
    expectSameTimestamp($group->ai_usage_reset_at, $firstPeriodEnd->timestamp);

    $payload['data']['object']['lines']['data'][0]['period']['end'] = $secondPeriodEnd->timestamp;

    $this->service->handleInvoicePaid($payload);
    $group->refresh();

    expect($group->ai_monthly_remaining)->toBe(30);
    expectSameTimestamp($group->ai_usage_reset_at, $firstPeriodEnd->timestamp);
    expect($group->ai_usage_reset_at->timestamp)->not->toBe($secondPeriodEnd->timestamp);
});

test('4-2-6: 【請求成功】 不明な price ID ではプラン更新しない', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();

    $group = makeBillableGroup([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 5,
    ]);

    $payload = makeInvoicePaidPayload([
        'lines' => [
            'data' => [
                [
                    'price' => ['id' => 'price_unknown'],
                    'period' => ['end' => $periodEnd->timestamp],
                ],
            ],
        ],
    ]);

    $this->service->handleInvoicePaid($payload);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(30);
    expectSameTimestamp($group->ai_usage_reset_at, $periodEnd->timestamp);
});

test('4-2-7: 【請求成功】 period.end なしでは renewBillingPeriod を呼ばない', function () {
    $resetAt = now()->addDays(10)->startOfSecond();

    $group = makeBillableGroup([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 5,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $payload = makeInvoicePaidPayload([
        'lines' => [
            'data' => [
                [
                    'price' => ['id' => $this->standardPriceId],
                    'period' => ['start' => now()->timestamp],
                ],
            ],
        ],
    ]);
    unset($payload['data']['object']['lines']['data'][0]['period']['end']);

    $this->service->handleInvoicePaid($payload);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(5);
    expectSameTimestamp($group->ai_usage_reset_at, $resetAt->timestamp);
});

test('4-2-8: 【請求成功】 event ID が空の payload でも正常処理される', function () {
    $periodEnd = now()->addDays(30)->startOfSecond();

    $group = makeBillableGroup([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
    ]);

    $payload = makeInvoicePaidPayload([
        'lines' => [
            'data' => [
                [
                    'price' => ['id' => $this->standardPriceId],
                    'period' => ['end' => $periodEnd->timestamp],
                ],
            ],
        ],
    ], '');

    $this->service->handleInvoicePaid($payload);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(30);
    expectSameTimestamp($group->ai_usage_reset_at, $periodEnd->timestamp);
});

test('4-2-9: 【請求成功】 lines.data が空配列の場合プラン更新も周期リセットも行わない', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 2,
    ]);
    $before = $group->fresh();

    $payload = makeInvoicePaidPayload();
    $payload['data']['object']['lines']['data'] = [];

    $this->service->handleInvoicePaid($payload);

    assertGroupUsageUnchanged($group, $before);
});

// ===== syncPlanFromSubscription() メソッドのテストケース =====

test('4-2-10: 【サブスク同期】 active な管理対象サブスクで STANDARD プランを付与する', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
    ]);

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('active'),
    );

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(30);
});

test('4-2-11: 【サブスク同期】 trialing な管理対象サブスクで STANDARD プランを付与する', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::FREE]);

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('trialing'),
    );

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD);
});

test('4-2-12: 【サブスク同期】 past_due な管理対象サブスクでも STANDARD プランを維持する', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::FREE]);

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('past_due'),
    );

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD);
});

test('4-2-13: 【サブスク同期】 canceled なサブスクで FREE に戻す', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => now()->addDays(10),
    ]);

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('canceled'),
    );

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::FREE)
        ->and($group->ai_monthly_remaining)->toBe(20);
});

test('4-2-14: 【サブスク同期】 unpaid なサブスクで FREE に戻す', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::STANDARD]);

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('unpaid'),
    );

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-15: 【サブスク同期】 incomplete なサブスクで FREE に戻す', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::STANDARD]);

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('incomplete'),
    );

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::FREE);
});

test('4-2-16: 【サブスク同期】 metadata なしのサブスクは管理対象として処理される', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::FREE]);

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('active', ['metadata' => []]),
    );

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD);
});

test('4-2-17: 【サブスク同期】 管理対象外サブスクはスキップする', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::STANDARD]);
    $before = $group->fresh();

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('active', ['metadata' => ['type' => 'other']]),
    );

    assertGroupUsageUnchanged($group, $before);
});

test('4-2-18: 【サブスク同期】 active でも不明な price ID ではプラン更新しない', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::FREE, 'ai_monthly_remaining' => 2]);
    $before = $group->fresh();

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('active', [
            'items' => [
                'data' => [
                    ['price' => ['id' => 'price_unknown']],
                ],
            ],
        ]),
    );

    assertGroupUsageUnchanged($group, $before);
});

test('4-2-19: 【サブスク同期】 active かつ管理対象だが items.data が空の場合プラン更新しない', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::FREE, 'ai_monthly_remaining' => 2]);
    $before = $group->fresh();

    $subscription = makeSubscriptionPayload('active');
    $subscription['items']['data'] = [];

    $this->service->syncPlanFromSubscription($group, $subscription);

    assertGroupUsageUnchanged($group, $before);
});

test('4-2-20: 【サブスク同期】 billing.subscription_type 設定が空の場合は全サブスクが管理対象外となる', function () {
    config(['billing.subscription_type' => '']);

    $group = makeBillableGroup(['plan' => GroupPlan::FREE, 'ai_monthly_remaining' => 2]);
    $before = $group->fresh();

    $this->service->syncPlanFromSubscription(
        $group,
        makeSubscriptionPayload('active'),
    );

    assertGroupUsageUnchanged($group, $before);
});

// ===== syncSubscriptionCancellationSchedule() メソッドのテストケース =====

function createWebhookSubscriptionRecord(Group $group, array $attributes = []): void
{
    $group->subscriptions()->create(array_merge([
        'type' => config('billing.subscription_type'),
        'stripe_id' => 'sub_test_' . str()->random(8),
        'stripe_status' => 'active',
        'stripe_price' => config('billing.price_ids.subscription_standard'),
        'ends_at' => null,
    ], $attributes));
}

test('4-2-21: 【解約予定同期】 解約キャンセル後は ends_at をクリアする', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = makeBillableGroup(['plan' => GroupPlan::STANDARD]);
    createWebhookSubscriptionRecord($group, [
        'ends_at' => $endsAt,
    ]);

    $this->service->syncSubscriptionCancellationSchedule(
        $group->fresh(),
        makeSubscriptionPayload('active', ['cancel_at_period_end' => false]),
    );

    $subscription = $group->fresh()->subscription(config('billing.subscription_type'));

    expect($subscription?->ends_at)->toBeNull();
});

test('4-2-22: 【解約予定同期】 cancel_at_period_end=true のとき ends_at は維持する', function () {
    $endsAt = now()->addDays(7)->startOfSecond();
    $group = makeBillableGroup(['plan' => GroupPlan::STANDARD]);
    createWebhookSubscriptionRecord($group, [
        'ends_at' => $endsAt,
    ]);

    $this->service->syncSubscriptionCancellationSchedule(
        $group->fresh(),
        makeSubscriptionPayload('active', ['cancel_at_period_end' => true]),
    );

    $subscription = $group->fresh()->subscription(config('billing.subscription_type'));

    expect($subscription?->ends_at?->toIso8601String())->toBe($endsAt->toIso8601String());
});

test('4-2-23: 【解約予定同期】 cancel_at_period_end=true かつ ends_at 未設定時は current_period_end から ends_at を設定する', function () {
    $periodEnd = now()->addDays(14)->startOfSecond();
    $group = makeBillableGroup(['plan' => GroupPlan::STANDARD]);
    createWebhookSubscriptionRecord($group, [
        'ends_at' => null,
    ]);

    $this->service->syncSubscriptionCancellationSchedule(
        $group->fresh(),
        makeSubscriptionPayload('active', [
            'cancel_at_period_end' => true,
            'current_period_end' => $periodEnd->timestamp,
        ]),
    );

    $subscription = $group->fresh()->subscription(config('billing.subscription_type'));

    expect($subscription?->ends_at?->toIso8601String())->toBe($periodEnd->toIso8601String());
});

test('4-2-24: 【解約予定同期】 cancel_at_period_end=true かつ ends_at 未設定時は cancel_at から ends_at を設定する', function () {
    $cancelAt = now()->addDays(22)->startOfSecond();
    $group = makeBillableGroup(['plan' => GroupPlan::STANDARD]);
    createWebhookSubscriptionRecord($group, [
        'ends_at' => null,
    ]);

    $this->service->syncSubscriptionCancellationSchedule(
        $group->fresh(),
        makeSubscriptionPayload('active', [
            'cancel_at_period_end' => true,
            'cancel_at' => $cancelAt->timestamp,
        ]),
    );

    $subscription = $group->fresh()->subscription(config('billing.subscription_type'));

    expect($subscription?->ends_at?->toIso8601String())->toBe($cancelAt->toIso8601String());
});

// ===== handleCheckoutSessionCompleted() メソッドのテストケース =====

test('4-2-25: 【パック購入】 買い切りパック購入時に ai_pack_remaining を加算する', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 2,
        'ai_pack_remaining' => 0,
    ]);

    $this->service->handleCheckoutSessionCompleted(
        makeCheckoutSessionPayload($group, 10),
    );

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(10)
        ->and($group->plan)->toBe(GroupPlan::FREE)
        ->and($group->ai_monthly_remaining)->toBe(2);
});

test('4-2-26: 【パック購入】 複数回購入で ai_pack_remaining が累積する', function () {
    $group = makeBillableGroup(['ai_pack_remaining' => 0]);

    $this->service->handleCheckoutSessionCompleted(
        makeCheckoutSessionPayload($group, 10, eventId: 'evt_checkout_1'),
    );
    $this->service->handleCheckoutSessionCompleted(
        makeCheckoutSessionPayload($group, 10, eventId: 'evt_checkout_2'),
    );

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(20);
});

test('4-2-27: 【パック購入】 metadata.type が pack 以外はスキップする', function () {
    $group = makeBillableGroup(['ai_pack_remaining' => 5]);

    $this->service->handleCheckoutSessionCompleted(
        makeCheckoutSessionPayload($group, 10, [
            'metadata' => [
                'type' => 'subscription',
                'group_id' => $group->id,
                'credits' => '10',
            ],
        ]),
    );

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(5);
});

test('4-2-28: 【パック購入】 payment_status が paid 以外はスキップする', function () {
    $group = makeBillableGroup(['ai_pack_remaining' => 5]);

    $this->service->handleCheckoutSessionCompleted(
        makeCheckoutSessionPayload($group, 10, ['payment_status' => 'unpaid']),
    );

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(5);
});

test('4-2-29: 【パック購入】 credits が 0 以下はスキップする', function () {
    $group = makeBillableGroup(['ai_pack_remaining' => 5]);

    $this->service->handleCheckoutSessionCompleted(
        makeCheckoutSessionPayload($group, 0),
    );

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(5);
});

test('4-2-30: 【パック購入】 group_id が空または不正ならスキップする', function () {
    $group = makeBillableGroup(['ai_pack_remaining' => 5]);

    $this->service->handleCheckoutSessionCompleted(
        makeCheckoutSessionPayload($group, 10, [
            'metadata' => [
                'type' => 'pack',
                'group_id' => '',
                'credits' => '10',
            ],
        ]),
    );

    $this->service->handleCheckoutSessionCompleted(
        makeCheckoutSessionPayload($group, 10, [
            'metadata' => [
                'type' => 'pack',
                'group_id' => 123,
                'credits' => '10',
            ],
        ]),
    );

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(5);
});

test('4-2-31: 【パック購入】 存在しない group_id はスキップする', function () {
    $group = makeBillableGroup(['ai_pack_remaining' => 5]);

    $this->service->handleCheckoutSessionCompleted([
        'id' => 'evt_checkout_missing_group',
        'data' => [
            'object' => [
                'metadata' => [
                    'type' => 'pack',
                    'group_id' => (string) str()->uuid(),
                    'credits' => '10',
                ],
                'payment_status' => 'paid',
            ],
        ],
    ]);

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(5);
});

test('4-2-32: 【パック購入】 同一 event ID の再送は二重加算しない', function () {
    $group = makeBillableGroup(['ai_pack_remaining' => 0]);

    $payload = makeCheckoutSessionPayload($group, 10, eventId: 'evt_duplicate_checkout');

    $this->service->handleCheckoutSessionCompleted($payload);
    $this->service->handleCheckoutSessionCompleted($payload);

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(10);
});

// ===== updateGroupPlan() メソッドのテストケース =====

test('4-2-33: 【プラン更新】 FREE から STANDARD へ変更すると月間残数が新上限になる', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
    ]);

    $this->service->updateGroupPlan($group, GroupPlan::STANDARD);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(30);
});

test('4-2-34: 【プラン更新】 同一プランへの更新では月間残数を変更しない', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
    ]);

    $this->service->updateGroupPlan($group, GroupPlan::STANDARD);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(20);
});

test('4-2-35: 【プラン更新】 STANDARD から FREE へ周期内変更では月間残数を維持する', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => now()->addDays(10),
    ]);

    $this->service->updateGroupPlan($group, GroupPlan::FREE);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::FREE)
        ->and($group->ai_monthly_remaining)->toBe(20);
});

test('4-2-36: 【プラン更新】 STANDARD から FREE へ周期終了後は月間残数を 0 にする', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => now()->subDay(),
    ]);

    $this->service->updateGroupPlan($group, GroupPlan::FREE);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::FREE)
        ->and($group->ai_monthly_remaining)->toBe(0);
});

test('4-2-37: 【プラン更新】 プラン変更しても ai_pack_remaining は変更しない', function () {
    $group = makeBillableGroup([
        'plan' => GroupPlan::FREE,
        'ai_pack_remaining' => 15,
    ]);

    $this->service->updateGroupPlan($group, GroupPlan::STANDARD);

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(15);
});

test('4-2-38: 【プラン更新】 削除済み Group はサイレントにスキップする', function () {
    $group = makeBillableGroup(['plan' => GroupPlan::FREE]);
    $groupId = $group->id;

    $group->delete();

    expect(fn () => $this->service->updateGroupPlan($group, GroupPlan::STANDARD))
        ->not->toThrow(Exception::class);

    expect(Group::query()->find($groupId))->toBeNull();
});
