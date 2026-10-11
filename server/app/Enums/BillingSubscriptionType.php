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
     * 請求履歴・次回請求表示用の翻訳済みラベル。
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD => __('api.billing.standard_plan_line'),
        };
    }

    /**
     * Square の Plan Variation ID から {@see self} を得る。
     * 未設定・不明な ID のときは null。
     */
    public static function subscriptionTypeFromSquarePlanVariationId(?string $planVariationId): ?self
    {
        if ($planVariationId === null || $planVariationId === '') {
            return null;
        }

        foreach (self::cases() as $type) {
            if ($type->configuredSquarePlanVariationId() === $planVariationId) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Square の Plan Variation ID から反映先 {@see GroupPlan} を得る（Webhook 等）。
     * 未設定・不明な ID のときは null。
     */
    public static function groupPlanFromSquarePlanVariationId(?string $planVariationId): ?GroupPlan
    {
        return self::subscriptionTypeFromSquarePlanVariationId($planVariationId)?->groupPlan();
    }

    /**
     * Square のサブスクリプション用 Plan Variation ID。
     * `billing.subscription_plan_ids` のキーは {@see $this->value} と一致。
     *
     * @throws HttpException プラン ID が未設定のとき
     */
    public function squarePlanVariationId(): string
    {
        $planVariationId = $this->configuredSquarePlanVariationId();

        if ($planVariationId === null) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                __('api.billing.price_not_configured'),
            );
        }

        return $planVariationId;
    }

    /**
     * サブスクリプション月額（円・表示・次回請求予定用）。
     * `billing.subscription_amounts` のキーは {@see $this->value} と一致。
     */
    public function squareSubscriptionAmount(): int
    {
        $amount = config('billing.subscription_amounts.' . $this->value);
        return is_numeric($amount) && (int) $amount > 0 ? (int) $amount : 0;
    }

    /**
     * Square のサブスクリプション用 Plan Variation ID。
     * `billing.subscription_plan_ids` のキーは {@see $this->value} と一致。
     */
    private function configuredSquarePlanVariationId(): ?string
    {
        $planVariationId = config('billing.subscription_plan_ids.' . $this->value);
        return is_string($planVariationId) && $planVariationId !== '' ? $planVariationId : null;
    }
}
