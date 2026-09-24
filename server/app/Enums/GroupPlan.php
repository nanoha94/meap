<?php

namespace App\Enums;

enum GroupPlan: string
{
    case FREE = 'free';
    case STANDARD = 'standard';
    case PRO = 'pro';
    case PRO_PLUS = 'pro_plus';

    /**
     * AI 利用上限など、アプリ内プラン枠の設定（課金プロバイダ非依存）。
     */
    public function monthlyLimit(): int
    {
        return (int) config("ai.plans.{$this->value}");
    }
}
