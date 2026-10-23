<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
     * Square API の YYYY-MM-DD 日付をアプリタイムゾーンの日始 Carbon に変換する。
     * current_period_start と canceled_at のように、その日の始まりを表す項目に使う。
     */
    public static function billingDateToStartOfDay(mixed $date): ?Carbon
    {
        return self::parseBillingDate($date)?->startOfDay()->startOfSecond();
    }

    /**
     * Square API の YYYY-MM-DD 日付をアプリタイムゾーンの日末 Carbon に変換する。
     * charged_through_date は「その日まで利用可能」なので、周期末日の 23:59:59 として扱う。
     */
    public static function billingDateToEndOfDay(mixed $date): ?Carbon
    {
        return self::parseBillingDate($date)?->endOfDay()->startOfSecond();
    }

    /**
     * 請求周期終了日（charged_through_date）を日末の Carbon に変換する。
     * charged_through_date が無い場合は canceled_date へフォールバックする（Billing / Webhook 共通）。
     */
    public static function periodEnd(mixed $chargedThroughDate, mixed $canceledDate = null): ?Carbon
    {
        if (is_string($chargedThroughDate) && $chargedThroughDate !== '') {
            return self::billingDateToEndOfDay($chargedThroughDate);
        }

        if ($canceledDate !== null && $canceledDate !== '') {
            return self::billingDateToEndOfDay($canceledDate);
        }

        return null;
    }

    /**
     * Square API の YYYY-MM-DD をアプリタイムゾーンの Carbon に変換する。
     * 空文字・非文字列は null。
     */
    private static function parseBillingDate(mixed $date): ?Carbon
    {
        if (! is_string($date) || $date === '') {
            return null;
        }

        return Carbon::parse($date, config('app.timezone'));
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
     * ID または status が不正なときは保存せず false を返す。
     *
     * @param  array<string, mixed>  $subscription
     */
    public static function upsertSubscriptionRecord(Group $group, array $subscription): bool
    {
        $subscriptionId = $subscription['id'] ?? null;
        if (! is_string($subscriptionId) || $subscriptionId === '') {
            Log::warning('upsertSubscriptionRecord skipped: subscription ID is missing.', [
                'group_id' => $group->id,
            ]);

            return false;
        }

        $status = self::normalizeStatus(
            $subscription['status'] ?? null,
            $subscription['canceled_date'] ?? null,
        );
        if ($status === null) {
            Log::warning('upsertSubscriptionRecord skipped: status is invalid.', [
                'group_id' => $group->id,
                'raw_status' => $subscription['status'] ?? null,
            ]);

            return false;
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
                'current_period_start' => self::billingDateToStartOfDay($subscription['start_date'] ?? null),
                'current_period_end' => $periodEnd,
                'canceled_at' => self::billingDateToStartOfDay($subscription['canceled_date'] ?? null),
            ],
        );

        return true;
    }
}
