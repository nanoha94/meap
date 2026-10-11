<?php

namespace App\Http\Controllers;

use App\Services\BillingWebhookService;
use Illuminate\Http\Request;
use Square\Utils\WebhooksHelper;
use Symfony\Component\HttpFoundation\Response;

class SquareWebhookController extends Controller
{
    public function __construct(
        private readonly BillingWebhookService $billingWebhookService,
    ) {}

    /**
     * Square Webhook を受信し、イベント種別に応じて BillingWebhookService を呼び出す。
     */
    public function handle(Request $request): Response
    {
        $signatureKey = (string) config('services.square.webhook_signature_key', '');
        $notificationUrl = (string) config('services.square.webhook_notification_url', '');
        $signature = (string) $request->header('x-square-hmacsha256-signature', '');
        $body = $request->getContent();

        if (
            $signatureKey === ''
            || $notificationUrl === ''
            || ! WebhooksHelper::verifySignature(
                $body,
                $signature,
                $signatureKey,
                $notificationUrl,
            )
        ) {
            return response('', Response::HTTP_FORBIDDEN);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($body, true) ?? [];

        switch ($payload['type'] ?? null) {
            /** サブスク請求成功の通知: プラン更新と利用回数リセットを行う。 */
            case 'invoice.payment_made':
                $this->billingWebhookService->handleInvoicePaymentMade($payload);
                break;
            /** サブスク作成・変更・解約確定（status=CANCELED/DEACTIVATED 等）: プラン同期 */
            case 'subscription.updated':
                $this->billingWebhookService->handleSubscriptionUpdated($payload);
                break;
            /** 支払い情報が更新された通知: 支払い情報の更新を行う。 */
            case 'payment.updated':
                $this->billingWebhookService->handlePaymentUpdated($payload);
                break;
        }

        return response('', Response::HTTP_OK);
    }
}
