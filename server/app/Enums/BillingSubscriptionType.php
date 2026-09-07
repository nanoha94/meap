<?php

namespace App\Enums;

enum BillingSubscriptionType: string
{
    case STANDARD = 'standard';

    public function configKey(): string
    {
        return match ($this) {
            self::STANDARD => 'subscription_standard',
        };
    }
}
