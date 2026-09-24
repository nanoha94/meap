<?php

namespace App\Services;

use Payjp\Charge;
use Payjp\Customer;
use Payjp\Payjp;
use Payjp\Subscription;

/**
 * PAY.JP PHP SDK の薄いラッパー（テストでモックしやすくするため）。
 */
class PayjpBillingClient
{
    public function __construct(?string $apiKey = null)
    {
        $key = $apiKey ?? (string) config('services.payjp.secret_key', '');

        if ($key !== '') {
            Payjp::setApiKey($key);
        }
    }

    /**
     * 顧客を作成する
     * @param  array<string, mixed>  $params
     * @return Customer
     */
    public function createCustomer(array $params): Customer
    {
        return Customer::create($params);
    }

    /**
     * 顧客情報を取得する
     * @param string $customerId
     * @return Customer
     */
    public function retrieveCustomer(string $customerId): Customer
    {
        return Customer::retrieve($customerId);
    }

    /**
     * 顧客情報を更新する
     * @param string $customerId
     * @param  array<string, mixed>  $params
     * @return Customer
     */
    public function updateCustomer(string $customerId, array $params): Customer
    {
        $customer = $this->retrieveCustomer($customerId);

        foreach ($params as $key => $value) {
            $customer->{$key} = $value;
        }

        return $customer->save();
    }

    /**
     * サブスクリプションを作成する
     * @param  array<string, mixed>  $params
     * @return Subscription
     */
    public function createSubscription(array $params): Subscription
    {
        return Subscription::create($params);
    }

    /**
     * サブスクリプションを取得する
     * @param string $subscriptionId
     * @return Subscription
     */
    public function retrieveSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId);
    }

    /**
     * サブスクリプションをキャンセルする
     * @param string $subscriptionId
     * @return Subscription
     */
    public function cancelSubscription(string $subscriptionId): Subscription
    {
        $subscription = $this->retrieveSubscription($subscriptionId);
        return $subscription->cancel();
    }

    /**
     * サブスクリプションを再開する
     * @param string $subscriptionId
     * @return Subscription
     */
    public function resumeSubscription(string $subscriptionId): Subscription
    {
        $subscription = $this->retrieveSubscription($subscriptionId);
        return $subscription->resume();
    }

    /**
     * 支払いを作成する
     * @param  array<string, mixed>  $params
     * @return Charge
     */
    public function createCharge(array $params): Charge
    {
        return Charge::create($params);
    }

    /**
     * 支払いを一覧取得する
     * @param  array<string, mixed>  $params
     * @return array<int, Charge>
     */
    public function listCharges(array $params): array
    {
        $collection = Charge::all($params);
        return $collection->data ?? [];
    }

    /**
     * トークンから顧客のカードを追加し、旧カードをすべて削除して 1 枚だけ残す
     * @param string $customerId
     * @param string $cardToken
     * @return Customer
     */
    public function addCustomerCardFromToken(string $customerId, string $cardToken): Customer
    {
        $customer = $this->retrieveCustomer($customerId);

        // 旧カード ID を控える（すべてのカード）
        $oldCardIds = array_map(
            fn($card) => $card->id,
            $customer->cards->data ?? [],
        );

        // 新カード追加 → デフォルトに設定
        $newCard = $customer->cards->create(['card' => $cardToken]);
        $customer->default_card = $newCard->id;
        $customer->save();

        // 旧カード削除（1枚のみ保持）
        foreach ($oldCardIds as $oldCardId) {
            $this->deleteCustomerCard($customerId, $oldCardId);
        }

        return $this->retrieveCustomer($customerId);
    }

    /**
     * 顧客のカードを削除する
     * @param string $customerId
     * @param string $cardId
     * @return void
     */
    public function deleteCustomerCard(string $customerId, string $cardId): void
    {
        $customer = $this->retrieveCustomer($customerId);
        $card = $customer->cards->retrieve($cardId);
        $card->delete();
    }
}
