<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\BillingCancelRequest;
use App\Http\Requests\Api\BillingCardDeleteRequest;
use App\Http\Requests\Api\BillingCardUpdateRequest;
use App\Http\Requests\Api\BillingInvoicesRequest;
use App\Http\Requests\Api\BillingPackPurchaseRequest;
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

    /**
     * @OA\Post(
     *     path="/billing/subscription/{subscriptionType}",
     *     summary="サブスクリプション開始",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="subscriptionType",
     *         in="path",
     *         required=true,
     *         description="サブスクリプション種別",
     *         @OA\Schema(type="string", enum={"standard"})
     *     ),
     *     @OA\RequestBody(ref="#/components/requestBodies/BillingSubscribeRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/BillingSubscribeSuccess"),
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
                $status = $this->billingService->createSubscription(
                    $group,
                    $request->user(),
                    $request->subscriptionType(),
                    $request->cardToken(),
                );
                $message = __('api.billing.subscribe_success');

                return $this->showResponse($status, $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }

    /**
     * @OA\Post(
     *     path="/billing/subscription/cancel",
     *     summary="サブスクリプションを解約する",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/BillingCancelSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function cancel(BillingCancelRequest $request): JsonResponse
    {
        $operation = __('operations.billing.cancel');
        $failedMessage = __('api.billing.cancel_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $status = $this->billingService->cancelSubscription($group);
                $message = __('api.billing.cancel_success');

                return $this->showResponse($status, $message);
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
     * @OA\Post(
     *     path="/billing/packs/{packType}",
     *     summary="買い切りパック購入",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="packType",
     *         in="path",
     *         required=true,
     *         description="パック種別",
     *         @OA\Schema(type="string", enum={"light", "value"})
     *     ),
     *     @OA\RequestBody(ref="#/components/requestBodies/BillingPackPurchaseRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/BillingPackPurchaseSuccess"),
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
                $status = $this->billingService->purchasePack(
                    $group,
                    $request->user(),
                    $request->packType(),
                    $request->cardToken(),
                );
                $message = __('api.billing.purchase_pack_success');

                return $this->showResponse($status, $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }

    /**
     * @OA\Post(
     *     path="/billing/card",
     *     summary="カード情報を登録・更新",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/BillingCardUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/BillingCardUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function updateCard(BillingCardUpdateRequest $request): JsonResponse
    {
        $operation = __('operations.billing.card');
        $failedMessage = __('api.billing.card_update_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $status = $this->billingService->updateCard(
                    $group,
                    $request->user(),
                    $request->cardToken(),
                );
                $message = __('api.billing.card_update_success');

                return $this->showResponse($status, $message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }

    /**
     * @OA\Delete(
     *     path="/billing/card",
     *     summary="登録カードを削除",
     *     tags={"Billing"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/BillingCardDeleteSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function deleteCard(BillingCardDeleteRequest $request): JsonResponse
    {
        $operation = __('operations.billing.card_delete');
        $failedMessage = __('api.billing.card_delete_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $this->billingService->deleteCard($group);
                $message = __('api.billing.card_delete_success');

                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation,
        );
    }
}
