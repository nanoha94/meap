<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Subscription;
use Carbon\Carbon;
use Square\Types\Subscription as SquareSubscription;

/**
 * BillingService と BillingWebhookService が共有する
 * サブスクリプション同期用の純関数ヘルパー。
 */
class SubscriptionHelper
{
    /**
     * サブスクリプション status をローカル DB 用（小文字）に正規化する。
     * 解約予定（canceled_date あり）の ACTIVE は canceled として扱う。
     * status が不明（null / 空文字 / 非 string は string 化後に空）のときは null を返す（DB を更新しない）。
     */
    public static function normalizeStatus(mixed $rawStatus, mixed $canceledDate): ?string
    {
        if ($rawStatus === null || $rawStatus === '') {
            return null;
        }

        if (! is_string($rawStatus)) {
            $rawStatus = (string) $rawStatus;
        }

        if ($rawStatus === '') {
            return null;
        }

        $status = strtolower($rawStatus);

        if ($canceledDate !== null && $canceledDate !== '' && $status === 'active') {
            return 'canceled';
        }

        return match ($status) {
            'deactivated' => 'canceled',
            default => $status,
        };
    }

    /**
     * Square API の YYYY-MM-DD 日付をアプリタイムゾーンの Carbon（日始まり）に変換する。
     */
    public static function billingDateToCarbon(mixed $date): ?Carbon
    {
        if (! is_string($date) || $date === '') {
            return null;
        }

        return Carbon::parse($date, config('app.timezone'))->startOfDay()->startOfSecond();
    }

    /**
     * 請求周期終了日（charged_through_date）を Carbon に変換する。
     * charged_through_date が無い場合は canceled_date へフォールバックする（Billing / Webhook 共通）。
     */
    public static function periodEnd(mixed $chargedThroughDate, mixed $canceledDate = null): ?Carbon
    {
        if (is_string($chargedThroughDate) && $chargedThroughDate !== '') {
            return self::billingDateToCarbon($chargedThroughDate);
        }

        if ($canceledDate !== null && $canceledDate !== '') {
            return self::billingDateToCarbon($canceledDate);
        }

        return null;
    }

    /**
     * ends_at（契約終了日）を決定する。
     * 解約予定・解約済みとみなすときだけ請求周期終了日（$periodEnd）を返し、それ以外は null。
     */
    public static function resolveEndsAt(string $status, mixed $canceledDate, ?Carbon $periodEnd): ?Carbon
    {
        if ($status !== 'canceled' && ($canceledDate === null || $canceledDate === '')) {
            return null;
        }

        return $periodEnd;
    }

    /**
     * Square SDK の Subscription を同期用の配列に変換する。
     *
     * @return array{
     *     id: string|null,
     *     customer_id: string|null,
     *     plan_variation_id: string|null,
     *     status: string|null,
     *     start_date: string|null,
     *     charged_through_date: string|null,
     *     canceled_date: string|null,
     * }
     */
    public static function normalizeFromSquare(SquareSubscription $subscription): array
    {
        $status = $subscription->getStatus();
        $id = $subscription->getId();

        return [
            'id' => is_string($id) && $id !== '' ? $id : null,
            'customer_id' => $subscription->getCustomerId(),
            'plan_variation_id' => $subscription->getPlanVariationId(),
            'status' => $status === null ? null : (string) $status,
            'start_date' => $subscription->getStartDate(),
            'charged_through_date' => $subscription->getChargedThroughDate(),
            'canceled_date' => $subscription->getCanceledDate(),
        ];
    }

    /**
     * 正規化済みサブスクリプション配列で subscriptions テーブルを upsert する。
     *
     * @param  array<string, mixed>  $subscription
     */
    public static function upsertSubscriptionRecord(Group $group, array $subscription): void
    {
        $subscriptionId = $subscription['id'] ?? null;
        if (! is_string($subscriptionId) || $subscriptionId === '') {
            return;
        }

        $status = self::normalizeStatus(
            $subscription['status'] ?? null,
            $subscription['canceled_date'] ?? null,
        );
        if ($status === null) {
            return;
        }

        $periodEnd = self::periodEnd(
            $subscription['charged_through_date'] ?? null,
            $subscription['canceled_date'] ?? null,
        );
        $endsAt = self::resolveEndsAt(
            $status,
            $subscription['canceled_date'] ?? null,
            $periodEnd,
        );

        $planVariationId = $subscription['plan_variation_id'] ?? null;

        Subscription::query()->updateOrCreate(
            ['square_subscription_id' => $subscriptionId],
            [
                'group_id' => $group->id,
                'square_plan_variation_id' => is_string($planVariationId) ? $planVariationId : null,
                'status' => $status,
                'ends_at' => $endsAt,
                'current_period_start' => self::billingDateToCarbon($subscription['start_date'] ?? null),
                'current_period_end' => $periodEnd,
                'canceled_at' => self::billingDateToCarbon($subscription['canceled_date'] ?? null),
            ],
        );
    }
}
