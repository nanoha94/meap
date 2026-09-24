<?php

namespace App\Enums;

use Symfony\Component\HttpKernel\Exception\HttpException;

enum BillingSubscriptionType: string
{
    case STANDARD = 'standard';

    /**
     * 課金開始後に {@see GroupPlan} へ反映する対応プラン。
     */
    public function groupPlan(): GroupPlan
    {
        return match ($this) {
            self::STANDARD => GroupPlan::STANDARD,
        };
    }

    /**
     * PAY.JP の Plan ID（`pln_...`）から反映先 {@see GroupPlan} を得る（Webhook 等）。
     * 未設定・不明な ID のときは null。
     */
    public static function groupPlanFromPayjpPlanId(?string $payjpPlanId): ?GroupPlan
    {
        if ($payjpPlanId === null || $payjpPlanId === '') {
            return null;
        }

        foreach (self::cases() as $type) {
            if ($type->configuredPayjpPlanId() === $payjpPlanId) {
                return $type->groupPlan();
            }
        }

        return null;
    }

    /**
     * PAY.JP のサブスクリプション用 Plan ID（`pln_...`）。
     * `billing.subscription_plan_ids` のキーは {@see $this->value} と一致。
     *
     * @throws HttpException プラン ID が未設定のとき
     */
    public function payjpSubscriptionPlanId(): string
    {
        $planId = $this->configuredPayjpPlanId();

        if ($planId === null) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                __('api.billing.price_not_configured'),
            );
        }

        return $planId;
    }

    /**
     * サブスクリプション月額（円・表示・次回請求予定用）。
     * `billing.subscription_amounts` のキーは {@see $this->value} と一致。
     */
    public function payjpSubscriptionAmount(): int
    {
        $amount = config('billing.subscription_amounts.' . $this->value);
        return is_numeric($amount) && (int) $amount > 0 ? (int) $amount : 0;
    }

    /**
     * PAY.JP のサブスクリプション用 Plan ID（`pln_...`）。
     * `billing.subscription_plan_ids` のキーは {@see $this->value} と一致。
     *
     * @throws HttpException プラン ID が未設定のとき
     */
    private function configuredPayjpPlanId(): ?string
    {
        $planId = config('billing.subscription_plan_ids.' . $this->value);
        return is_string($planId) && $planId !== '' ? $planId : null;
    }
}
