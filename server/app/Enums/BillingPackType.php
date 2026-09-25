<?php

namespace App\Enums;

use Symfony\Component\HttpKernel\Exception\HttpException;

enum BillingPackType: string
{
    case LIGHT = 'light';
    case VALUE = 'value';

    /**
     * パック表示名。
     */
    public function label(): string
    {
        return match ($this) {
            self::LIGHT => __('api.billing.pack_label_light'),
            self::VALUE => __('api.billing.pack_label_value'),
        };
    }

    /**
     * 付与 AI クレジット数。`billing.pack_credits` のキーは {@see $this->value} と一致。
     */
    public function credits(): int
    {
        return (int) config("billing.pack_credits.{$this->value}");
    }

    /**
     * 買い切りパックの都度課金金額（円）。
     * `billing.pack_prices` のキーは {@see $this->value} と一致。
     *
     * @throws HttpException 金額が未設定のとき
     */
    public function payjpPackPrice(): int
    {
        $price = config('billing.pack_prices.' . $this->value);

        if (! is_numeric($price) || (int) $price <= 0) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                __('api.billing.price_not_configured'),
            );
        }

        return (int) $price;
    }
}
