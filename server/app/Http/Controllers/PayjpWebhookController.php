<?php

namespace App\Http\Controllers;

use App\Services\BillingWebhookService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayjpWebhookController extends Controller
{
    public function __construct(
        private readonly BillingWebhookService $billingWebhookService,
    ) {}

    /**
     * PAY.JP Webhook を受信し、イベント種別に応じて BillingWebhookService を呼び出す。
     */
    public function handle(Request $request): Response
    {
        $expectedToken = (string) config('services.payjp.webhook_token', '');
        $providedToken = (string) $request->header('X-Payjp-Webhook-Token', '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response('', Response::HTTP_FORBIDDEN);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        switch ($payload['type'] ?? null) {
            case 'subscription.renewed':
                $this->billingWebhookService->handleSubscriptionRenewed($payload);
                break;
            case 'subscription.created':
            case 'subscription.updated':
                $this->billingWebhookService->handleSubscriptionChanged($payload);
                break;
            case 'subscription.deleted':
                $this->billingWebhookService->handleSubscriptionDeleted($payload);
                break;
            case 'subscription.paused':
                $this->billingWebhookService->handleSubscriptionPaused($payload);
                break;
            case 'charge.failed':
                $this->billingWebhookService->handleChargeFailed($payload);
                break;
        }

        return response('', Response::HTTP_OK);
    }
}
