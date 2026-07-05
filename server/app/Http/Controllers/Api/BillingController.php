<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\BillingInvoicesRequest;
use App\Http\Requests\Api\BillingPackPurchaseRequest;
use App\Http\Requests\Api\BillingPortalRequest;
use App\Http\Requests\Api\BillingResumeRequest;
use App\Http\Requests\Api\BillingStatusRequest;
use App\Http\Requests\Api\BillingSubscribeRequest;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;

class BillingController extends ApiController
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * @OA\Post(
     *     path="/billing/subscribe/{subscriptionType}",
     *     summary="サブスクリプション開始（Stripe Checkout）",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="subscriptionType",
     *         in="path",
     *         required=true,
     *         description="サブスクリプション種別",
     *         @OA\Schema(type="string", enum={"standard"})
     *     ),
     *     @OA\Response(response=200, ref="#/components/responses/BillingCheckoutSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function subscribe(BillingSubscribeRequest $request): JsonResponse
    {
        $operation = __('operations.billing.subscribe');
        $failedMessage = __('api.billing.subscribe_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $checkoutUrl = $this->billingService->createSubscriptionCheckout($group, $request->user(), $request->subscriptionType());
                $message = __('api.billing.checkout_created');

                return $this->showResponse(['checkoutUrl' => $checkoutUrl], $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }

    /**
     * @OA\Post(
     *     path="/billing/portal",
     *     summary="Stripe Customer Portal セッション作成",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/BillingPortalSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function portal(BillingPortalRequest $request): JsonResponse
    {
        $operation = __('operations.billing.portal');
        $failedMessage = __('api.billing.portal_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $portalUrl = $this->billingService->createPortalSession($group);
                $message = __('api.billing.portal_created');

                return $this->showResponse(['portalUrl' => $portalUrl], $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }

    /**
     * @OA\Post(
     *     path="/billing/packs/{packType}",
     *     summary="買い切りパック購入（Stripe Checkout）",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="packType",
     *         in="path",
     *         required=true,
     *         description="パック種別",
     *         @OA\Schema(type="string", enum={"light", "value"})
     *     ),
     *     @OA\Response(response=200, ref="#/components/responses/BillingCheckoutSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function purchasePack(BillingPackPurchaseRequest $request): JsonResponse
    {
        $operation = __('operations.billing.purchase_pack');
        $failedMessage = __('api.billing.purchase_pack_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $packType = $request->packType();
                $checkoutUrl = $this->billingService->createPackCheckout($group, $request->user(), $packType);
                $message = __('api.billing.checkout_created');

                return $this->showResponse(['checkoutUrl' => $checkoutUrl], $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }

    /**
     * @OA\Post(
     *     path="/billing/subscription/resume",
     *     summary="予定されているプラン変更を取り消してサブスクリプションを継続",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/BillingResumeSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function resume(BillingResumeRequest $request): JsonResponse
    {
        $operation = __('operations.billing.resume');
        $failedMessage = __('api.billing.resume_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $this->billingService->resumeSubscription($group);
                $status = $this->billingService->getBillingStatus($group);
                $message = __('api.billing.resume_success');

                return $this->showResponse($status, $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }

    /**
     * @OA\Get(
     *     path="/billing/status",
     *     summary="課金・サブスクリプション状態を取得",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/BillingStatusSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function status(BillingStatusRequest $request): JsonResponse
    {
        $operation = __('operations.billing.status');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.billing_status')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $status = $this->billingService->getBillingStatus($group);
                $message = __('api.retrieved', ['attribute' => __('api.attributes.billing_status')]);

                return $this->showResponse($status, $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }

    /**
     * @OA\Get(
     *     path="/billing/invoices",
     *     summary="請求履歴と次回お支払い予定を取得",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/BillingInvoicesSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function invoices(BillingInvoicesRequest $request): JsonResponse
    {
        $operation = __('operations.billing.invoices');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.billing_invoices')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $invoices = $this->billingService->getInvoices($group);
                $message = __('api.retrieved', ['attribute' => __('api.attributes.billing_invoices')]);

                return $this->showResponse($invoices, $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }
}
