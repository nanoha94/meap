<?php

namespace App\Http\Controllers;

use App\Enums\GroupPlan;
use App\Models\Group;
use App\Services\BillingWebhookService;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function __construct(
        private readonly BillingWebhookService $billingWebhookService,
    ) {
        parent::__construct();
    }

    /**
     * サブスクリプション作成時にプランを同期する。
     * customer.subscription.createdイベントのハンドラー
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        if ($group = $this->findGroupByStripeId($payload['data']['object']['customer'] ?? null)) {
            $this->billingWebhookService->syncPlanFromSubscription($group, $payload['data']['object']);
            $this->billingWebhookService->syncSubscriptionCancellationSchedule($group, $payload['data']['object']);
        }

        return $response;
    }

    /**
     * サブスクリプション更新時にプランを同期する。
     * customer.subscription.updatedイベントのハンドラー
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): ?Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        if ($group = $this->findGroupByStripeId($payload['data']['object']['customer'] ?? null)) {
            $this->billingWebhookService->syncPlanFromSubscription($group, $payload['data']['object']);
            $this->billingWebhookService->syncSubscriptionCancellationSchedule($group, $payload['data']['object']);
        }

        return $response;
    }

    /**
     * サブスクリプション解約時にプランをFREEに戻す。
     * customer.subscription.deletedイベントのハンドラー
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        if ($group = $this->findGroupByStripeId($payload['data']['object']['customer'] ?? null)) {
            $this->billingWebhookService->updateGroupPlan($group, GroupPlan::FREE);
        }

        return $response;
    }

    /**
     * 請求成功時にプランを同期する。
     * invoice.paidイベントのハンドラー
     * @param  array<string, mixed>  $payload
     */
    protected function handleInvoicePaid(array $payload): Response
    {
        $this->billingWebhookService->handleInvoicePaid($payload);

        return $this->successMethod();
    }

    /**
     * 買い切りパック購入時に ai_pack_remaining を加算する。
     * checkout.session.completedイベントのハンドラー
     * @param  array<string, mixed>  $payload
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $this->billingWebhookService->handleCheckoutSessionCompleted($payload);

        return $this->successMethod();
    }

    /**
     * Stripe顧客IDからGroupを取得する。
     * Cashier::useCustomerModel(Group::class) を前提とする。
     * @param  string|null  $stripeCustomerId
     * @return Group|null
     */
    private function findGroupByStripeId(?string $stripeCustomerId): ?Group
    {
        $billable = $this->getUserByStripeId($stripeCustomerId);

        return $billable instanceof Group ? $billable : null;
    }
}
