<?php

namespace App\Enums;

enum GroupPlan: string
{
    case FREE = 'free';
    case STANDARD = 'standard';
    case PRO = 'pro';
    case PRO_PLUS = 'pro_plus';

    public function monthlyLimit(): int
    {
        return (int) config("ai.plans.{$this->value}");
    }
}
