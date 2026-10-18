<?php

use App\Models\Group;
use App\Models\Subscription;
use App\Services\SubscriptionHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Square\Types\Subscription as SquareSubscription;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.timezone' => 'Asia/Tokyo']);
});

function subscriptionHelperExpectedCarbon(string $date): Carbon
{
    return Carbon::parse($date, config('app.timezone'))->startOfDay()->startOfSecond();
}

function makeSubscriptionHelperSquareSdk(array $overrides = []): SquareSubscription
{
    if (isset($overrides['plan_variation_id'])) {
        $overrides['planVariationId'] = $overrides['plan_variation_id'];
        unset($overrides['plan_variation_id']);
    }

    if (isset($overrides['charged_through_date'])) {
        $overrides['chargedThroughDate'] = $overrides['charged_through_date'];
        unset($overrides['charged_through_date']);
    }

    if (isset($overrides['canceled_date'])) {
        $overrides['canceledDate'] = $overrides['canceled_date'];
        unset($overrides['canceled_date']);
    }

    if (isset($overrides['start_date'])) {
        $overrides['startDate'] = $overrides['start_date'];
        unset($overrides['start_date']);
    }

    if (isset($overrides['customer_id'])) {
        $overrides['customerId'] = $overrides['customer_id'];
        unset($overrides['customer_id']);
    }

    if (isset($overrides['status'])) {
        $overrides['status'] = strtoupper((string) $overrides['status']);
    }

    return new SquareSubscription(array_merge([
        'id' => 'sub_helper_test_1',
        'customerId' => 'cus_helper_1',
        'status' => 'ACTIVE',
        'planVariationId' => 'pln_standard_test',
        'startDate' => '2026-01-15',
        'chargedThroughDate' => '2026-02-15',
    ], $overrides));
}

// ===== periodEnd() メソッドのテストケース =====

test('4-10-1: 【請求周期終了日】 charged_through_date を Carbon に変換する', function () {
    $result = SubscriptionHelper::periodEnd('2026-03-20', null);

    expect($result?->eq(subscriptionHelperExpectedCarbon('2026-03-20')))->toBeTrue();
});

test('4-10-2: 【請求周期終了日】 charged_through_date 欠落時 canceled_date にフォールバックする', function () {
    $result = SubscriptionHelper::periodEnd(null, '2026-04-01');

    expect($result?->eq(subscriptionHelperExpectedCarbon('2026-04-01')))->toBeTrue();
});

test('4-10-3: 【請求周期終了日】 日付が両方欠落なら null を返す', function () {
    expect(SubscriptionHelper::periodEnd(null, null))->toBeNull()
        ->and(SubscriptionHelper::periodEnd('', ''))->toBeNull();
});

// ===== resolveEndsAt() メソッドのテストケース =====

test('4-10-4: 【契約終了日】 active かつ canceled_date なしなら null', function () {
    $periodEnd = subscriptionHelperExpectedCarbon('2026-05-01');

    expect(SubscriptionHelper::resolveEndsAt('active', null, $periodEnd))->toBeNull();
});

test('4-10-5: 【契約終了日】 canceled なら periodEnd を返す', function () {
    $periodEnd = subscriptionHelperExpectedCarbon('2026-05-01');

    $result = SubscriptionHelper::resolveEndsAt('canceled', null, $periodEnd);

    expect($result?->eq($periodEnd))->toBeTrue();
});

test('4-10-6: 【契約終了日】 canceled_date ありなら periodEnd を返す', function () {
    $periodEnd = subscriptionHelperExpectedCarbon('2026-05-01');

    $result = SubscriptionHelper::resolveEndsAt('active', '2026-05-01', $periodEnd);

    expect($result?->eq($periodEnd))->toBeTrue();
});

// ===== normalizeFromSquare() メソッドのテストケース =====

test('4-10-7: 【Square 正規化】 Subscription の各フィールドを配列に変換する', function () {
    $sdk = makeSubscriptionHelperSquareSdk([
        'id' => 'sub_norm_1',
        'customer_id' => 'cus_norm_1',
        'plan_variation_id' => 'pln_var_1',
        'status' => 'ACTIVE',
        'start_date' => '2026-01-10',
        'charged_through_date' => '2026-02-10',
        'canceled_date' => '2026-02-10',
    ]);

    $normalized = SubscriptionHelper::normalizeFromSquare($sdk);

    expect($normalized)->toBe([
        'id' => 'sub_norm_1',
        'customer_id' => 'cus_norm_1',
        'plan_variation_id' => 'pln_var_1',
        'status' => 'ACTIVE',
        'start_date' => '2026-01-10',
        'charged_through_date' => '2026-02-10',
        'canceled_date' => '2026-02-10',
    ]);
});

test('4-10-8: 【Square 正規化】 id が空文字なら null にする', function () {
    $sdk = makeSubscriptionHelperSquareSdk(['id' => '']);

    expect(SubscriptionHelper::normalizeFromSquare($sdk)['id'])->toBeNull();
});

// ===== upsertSubscriptionRecord() メソッドのテストケース =====

test('4-10-9: 【サブスク upsert】 正規化配列から subscriptions を新規作成する', function () {
    $group = Group::factory()->create();
    $periodEnd = '2026-06-30';

    $upserted = SubscriptionHelper::upsertSubscriptionRecord($group, [
        'id' => 'sub_upsert_new',
        'plan_variation_id' => 'pln_standard_test',
        'status' => 'ACTIVE',
        'start_date' => '2026-06-01',
        'charged_through_date' => $periodEnd,
        'canceled_date' => null,
    ]);

    $subscription = Subscription::query()->where('square_subscription_id', 'sub_upsert_new')->first();

    expect($upserted)->toBeTrue()
        ->and($subscription)->not->toBeNull()
        ->and($subscription->group_id)->toBe($group->id)
        ->and($subscription->status)->toBe('active')
        ->and($subscription->ends_at)->toBeNull()
        ->and($subscription->current_period_end?->eq(subscriptionHelperExpectedCarbon($periodEnd)))->toBeTrue()
        ->and($subscription->current_period_start?->eq(subscriptionHelperExpectedCarbon('2026-06-01')))->toBeTrue();
});

test('4-10-10: 【サブスク upsert】 解約予定（canceled_date 付き ACTIVE）で ends_at を設定する', function () {
    $group = Group::factory()->create();
    $periodEnd = '2026-07-31';

    $upserted = SubscriptionHelper::upsertSubscriptionRecord($group, [
        'id' => 'sub_upsert_cancel_scheduled',
        'plan_variation_id' => 'pln_standard_test',
        'status' => 'ACTIVE',
        'start_date' => '2026-07-01',
        'charged_through_date' => $periodEnd,
        'canceled_date' => $periodEnd,
    ]);

    $subscription = Subscription::query()->where('square_subscription_id', 'sub_upsert_cancel_scheduled')->first();

    expect($upserted)->toBeTrue()
        ->and($subscription?->status)->toBe('canceled')
        ->and($subscription?->ends_at?->eq(subscriptionHelperExpectedCarbon($periodEnd)))->toBeTrue()
        ->and($subscription?->canceled_at?->eq(subscriptionHelperExpectedCarbon($periodEnd)))->toBeTrue();
});

test('4-10-11: 【サブスク upsert】 charged_through_date 欠落時 canceled_date で current_period_end を補完する', function () {
    $group = Group::factory()->create();
    $canceledDate = '2026-08-15';

    $upserted = SubscriptionHelper::upsertSubscriptionRecord($group, [
        'id' => 'sub_upsert_period_fallback',
        'plan_variation_id' => 'pln_standard_test',
        'status' => 'ACTIVE',
        'start_date' => '2026-08-01',
        'charged_through_date' => null,
        'canceled_date' => $canceledDate,
    ]);

    $subscription = Subscription::query()->where('square_subscription_id', 'sub_upsert_period_fallback')->first();

    expect($upserted)->toBeTrue()
        ->and($subscription?->current_period_end?->eq(subscriptionHelperExpectedCarbon($canceledDate)))->toBeTrue();
});

test('4-10-12: 【サブスク upsert】 既存レコードを square_subscription_id で更新する', function () {
    $group = Group::factory()->create();
    $group->subscriptions()->create([
        'square_subscription_id' => 'sub_upsert_update',
        'square_plan_variation_id' => 'pln_old',
        'status' => 'active',
        'ends_at' => null,
        'current_period_end' => null,
    ]);

    $upserted = SubscriptionHelper::upsertSubscriptionRecord($group, [
        'id' => 'sub_upsert_update',
        'plan_variation_id' => 'pln_standard_test',
        'status' => 'CANCELED',
        'start_date' => '2026-09-01',
        'charged_through_date' => '2026-09-30',
        'canceled_date' => '2026-09-30',
    ]);

    expect($upserted)->toBeTrue()
        ->and(Subscription::query()->where('square_subscription_id', 'sub_upsert_update')->count())->toBe(1);

    $subscription = Subscription::query()->where('square_subscription_id', 'sub_upsert_update')->first();

    expect($subscription?->status)->toBe('canceled')
        ->and($subscription?->square_plan_variation_id)->toBe('pln_standard_test');
});

test('4-10-13: 【サブスク upsert】 subscription id 欠落時は DB を更新しない', function () {
    $group = Group::factory()->create();

    $upserted = SubscriptionHelper::upsertSubscriptionRecord($group, [
        'id' => '',
        'status' => 'ACTIVE',
    ]);

    expect($upserted)->toBeFalse()
        ->and(Subscription::query()->count())->toBe(0);
});

test('4-10-14: 【サブスク upsert】 status 欠落時は DB を更新しない', function () {
    $group = Group::factory()->create();

    $upserted = SubscriptionHelper::upsertSubscriptionRecord($group, [
        'id' => 'sub_no_status',
        'status' => null,
        'charged_through_date' => '2026-10-01',
    ]);

    expect($upserted)->toBeFalse()
        ->and(Subscription::query()->count())->toBe(0);
});
