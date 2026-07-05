<?php

namespace App\Traits;

trait ExtractsSubscriptionEndsAt
{
    /**
     * Stripe サブスクリプションから終了日の Unix タイムスタンプを取得する。
     *
     * current_period_end を優先し、未設定かつ cancel_at_period_end=true のとき cancel_at を使う。
     *
     * @param  object|array<string, mixed>  $stripeSubscription
     */
    private function extractEndsAtTimestamp(object|array $stripeSubscription): ?int
    {
        // current_period_end が設定されている場合はそれを返す
        $currentPeriodEnd = is_array($stripeSubscription)
            ? ($stripeSubscription['current_period_end'] ?? null)
            : ($stripeSubscription->current_period_end ?? null);

        if ($currentPeriodEnd !== null) {
            return (int) $currentPeriodEnd;
        }

        // cancel_at_period_end が true の場合は cancel_at にフォールバックする
        $cancelAtPeriodEnd = is_array($stripeSubscription)
            ? ($stripeSubscription['cancel_at_period_end'] ?? false)
            : ($stripeSubscription->cancel_at_period_end ?? false);

        if (! $cancelAtPeriodEnd) {
            return null;
        }

        // cancel_at が設定されている場合はそれを返す
        $cancelAt = is_array($stripeSubscription)
            ? ($stripeSubscription['cancel_at'] ?? null)
            : ($stripeSubscription->cancel_at ?? null);

        return $cancelAt !== null ? (int) $cancelAt : null;
    }
}
