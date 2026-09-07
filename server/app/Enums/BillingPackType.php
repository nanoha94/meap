<?php

namespace App\Enums;

enum BillingPackType: string
{
    case LIGHT = 'light';
    case VALUE = 'value';

    public function configKey(): string
    {
        return match ($this) {
            self::LIGHT => 'pack_light',
            self::VALUE => 'pack_value',
        };
    }

    public function credits(): int
    {
        return (int) config("billing.pack_credits.{$this->value}");
    }
}
