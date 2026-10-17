<?php

namespace App\Services;

use Illuminate\Support\Str;
use Square\Cards\Requests\CreateCardRequest;
use Square\Cards\Requests\DisableCardsRequest;
use Square\Customers\Requests\CreateCustomerRequest;
use Square\Customers\Requests\GetCustomersRequest;
use Square\Environments;
use Square\Exceptions\SquareApiException;
use Square\SquareClient;
use Square\Subscriptions\Requests\CancelSubscriptionsRequest;
use Square\Subscriptions\Requests\CreateSubscriptionRequest;
use Square\Subscriptions\Requests\DeleteActionSubscriptionsRequest;
use Square\Subscriptions\Requests\GetSubscriptionsRequest;
use Square\Invoices\Requests\GetInvoicesRequest;
use Square\Orders\Requests\GetOrdersRequest;
use Square\Payments\Requests\CreatePaymentRequest;
use Square\Payments\Requests\GetPaymentsRequest;
use Square\Payments\Requests\ListPaymentsRequest;
use Square\Types\Card;
use Square\Types\Customer;
use Square\Types\Invoice;
use Square\Types\Money;
use Square\Types\Order;
use Square\Types\Payment;
use Square\Types\Subscription;
use Square\Types\Tender;

/**
 * Square PHP SDK の薄いラッパー（テストでモックしやすくするため）。
 */
class SquareBillingClient
{
    private ?SquareClient $client = null;

    public function __construct(
        private readonly ?string $accessToken = null,
        private readonly ?string $baseUrl = null,
    ) {}

    /**
     * Square Customers API で顧客を作成する。
     * @param  array<string, mixed>  $params
     * @return \Square\Types\Customer
     */
    public function createCustomer(array $params): Customer
    {
        $request = new CreateCustomerRequest([
            'idempotencyKey' => $params['idempotencyKey'] ?? Str::uuid()->toString(),
            'emailAddress' => $params['emailAddress'] ?? null,
            'note' => $params['note'] ?? null,
            'referenceId' => isset($params['referenceId']) ? (string) $params['referenceId'] : null,
            'givenName' => $params['givenName'] ?? null,
            'familyName' => $params['familyName'] ?? null,
        ]);

        $response = $this->client()->customers->create($request);
        $this->throwIfResponseHasErrors($response->getErrors(), 'createCustomer');

        $customer = $response->getCustomer();
        if ($customer === null || $customer->getId() === null) {
            throw new SquareApiException('Square createCustomer returned no customer', 502, null);
        }

        return $customer;
    }

    public function retrieveCustomer(string $customerId): Customer
    {
        $response = $this->client()->customers->get(
            new GetCustomersRequest(['customerId' => $customerId]),
        );
        $this->throwIfResponseHasErrors($response->getErrors(), 'retrieveCustomer');

        $customer = $response->getCustomer();
        if ($customer === null) {
            throw new SquareApiException('Square retrieveCustomer returned no customer', 404, null);
        }

        return $customer;
    }

    /**
     * Square Cards API でカードを作成する。
     * @param  string  $customerId
     * @param  string  $sourceId
     * @param  string|null  $verificationToken  CreateCard の任意 verification_token。通常は省略
     * @return \Square\Types\Card
     */
    public function createCard(
        string $customerId,
        string $sourceId,
        ?string $verificationToken = null,
    ): Card {
        $values = [
            'idempotencyKey' => Str::uuid()->toString(),
            'sourceId' => $sourceId,
            'card' => new Card(['customerId' => $customerId]),
        ];

        if (is_string($verificationToken) && $verificationToken !== '') {
            $values['verificationToken'] = $verificationToken;
        }

        $request = new CreateCardRequest($values);

        $response = $this->client()->cards->create($request);
        $this->throwIfResponseHasErrors($response->getErrors(), 'createCard');

        $card = $response->getCard();
        if ($card === null || $card->getId() === null) {
            throw new SquareApiException('Square createCard returned no card', 502, null);
        }

        return $card;
    }

    /**
     * トークンからカードを追加し、旧カードがあれば無効化する（1 枚運用）。
     * @param  string  $customerId
     * @param  string  $sourceId
     * @param  string|null  $oldCardId
     * @param  string|null  $verificationToken  CreateCard の任意 verification_token。通常は省略
     * @return \Square\Types\Card
     */
    public function addCustomerCardFromToken(
        string $customerId,
        string $sourceId,
        ?string $oldCardId = null,
        ?string $verificationToken = null,
    ): Card {
        $card = $this->createCard($customerId, $sourceId, $verificationToken);

        if (is_string($oldCardId) && $oldCardId !== '') {
            $this->disableCard($oldCardId);
        }

        return $card;
    }

    /**
     * Square Cards API でカードを無効化する。
     * @param  string  $cardId
     * @return void
     */
    public function disableCard(string $cardId): void
    {
        $response = $this->client()->cards->disable(
            new DisableCardsRequest(['cardId' => $cardId]),
        );
        $this->throwIfResponseHasErrors($response->getErrors(), 'disableCard');
    }

    /**
     * Square Subscriptions API でサブスクリプションを作成する。
     *
     * @param  array<string, mixed>  $params  customerId, planVariationId, cardId, locationId（省略時は config）
     *
     * @throws \InvalidArgumentException planVariationId または cardId が未指定のとき
     * @throws SquareApiException
     */
    public function createSubscription(array $params): Subscription
    {
        $locationId = $params['locationId'] ?? (string) config('services.square.location_id', '');
        if ($locationId === '') {
            throw new \RuntimeException('SQUARE_LOCATION_ID is not configured.');
        }

        $planVariationId = isset($params['planVariationId']) ? (string) $params['planVariationId'] : '';
        if ($planVariationId === '') {
            throw new \InvalidArgumentException('planVariationId is required for createSubscription.');
        }

        $cardId = isset($params['cardId']) ? (string) $params['cardId'] : '';
        if ($cardId === '') {
            throw new \InvalidArgumentException('cardId is required for createSubscription.');
        }

        $request = new CreateSubscriptionRequest([
            'idempotencyKey' => $params['idempotencyKey'] ?? Str::uuid()->toString(),
            'locationId' => $locationId,
            'customerId' => (string) $params['customerId'],
            'planVariationId' => $planVariationId,
            'cardId' => $cardId,
            'timezone' => $params['timezone'] ?? config('app.timezone'),
        ]);

        $response = $this->client()->subscriptions->create($request);
        $this->throwIfResponseHasErrors($response->getErrors(), 'createSubscription');

        return $this->requireSubscription($response->getSubscription(), 'createSubscription');
    }

    /**
     * Square Subscriptions API でサブスクリプションを取得する。
     * @param  string  $subscriptionId
     * @return \Square\Types\Subscription
     */
    public function retrieveSubscription(string $subscriptionId): Subscription
    {
        $response = $this->client()->subscriptions->get(
            new GetSubscriptionsRequest(['subscriptionId' => $subscriptionId]),
        );
        $this->throwIfResponseHasErrors($response->getErrors(), 'retrieveSubscription');

        return $this->requireSubscription($response->getSubscription(), 'retrieveSubscription');
    }

    /**
     * Square Orders API で注文を取得する（Invoice の order_id から Payment ID を得るため）。
     *
     * @throws SquareApiException
     */
    public function retrieveOrder(string $orderId): Order
    {
        $response = $this->client()->orders->get(
            new GetOrdersRequest(['orderId' => $orderId]),
        );
        $this->throwIfResponseHasErrors($response->getErrors(), 'retrieveOrder');

        $order = $response->getOrder();
        if ($order === null || $order->getId() === null) {
            throw new SquareApiException('Square retrieveOrder returned no order', 502, null);
        }

        return $order;
    }

    /**
     * Square Invoices API で請求書を取得する。
     *
     * @throws SquareApiException
     */
    public function retrieveInvoice(string $invoiceId): Invoice
    {
        $response = $this->client()->invoices->get(
            new GetInvoicesRequest(['invoiceId' => $invoiceId]),
        );
        $this->throwIfResponseHasErrors($response->getErrors(), 'retrieveInvoice');

        $invoice = $response->getInvoice();
        if ($invoice === null || $invoice->getId() === null) {
            throw new SquareApiException('Square retrieveInvoice returned no invoice', 502, null);
        }

        return $invoice;
    }

    /**
     * Square Payments API で支払いを取得する。
     *
     * @throws SquareApiException
     */
    public function retrievePayment(string $paymentId): Payment
    {
        $response = $this->client()->payments->get(
            new GetPaymentsRequest(['paymentId' => $paymentId]),
        );
        $this->throwIfResponseHasErrors($response->getErrors(), 'retrievePayment');

        $payment = $response->getPayment();
        if ($payment === null || $payment->getId() === null) {
            throw new SquareApiException('Square retrievePayment returned no payment', 502, null);
        }

        return $payment;
    }

    /**
     * 注文に紐づく最初の Tender から Square Payment ID を返す。見つからなければ null。
     */
    public function paymentIdFromOrder(Order $order): ?string
    {
        foreach ($order->getTenders() ?? [] as $tender) {
            if (! $tender instanceof Tender) {
                continue;
            }

            $paymentId = $tender->getPaymentId();
            if (is_string($paymentId) && $paymentId !== '') {
                return $paymentId;
            }

            $tenderId = $tender->getId();
            if (is_string($tenderId) && $tenderId !== '') {
                return $tenderId;
            }
        }

        return null;
    }

    /**
     * Subscription.invoice_ids の先頭 ID を返す（Square 仕様上、新しい請求書が先頭）。
     */
    public function latestSubscriptionInvoiceId(Subscription $subscription): ?string
    {
        foreach ($subscription->getInvoiceIds() ?? [] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * サブスクに紐づく最新請求書を GetInvoice で取得する。ID が無い・API 失敗時は null。
     */
    public function retrieveLatestSubscriptionInvoice(Subscription $subscription): ?Invoice
    {
        $invoiceId = $this->latestSubscriptionInvoiceId($subscription);
        if ($invoiceId === null) {
            return null;
        }

        try {
            return $this->retrieveInvoice($invoiceId);
        } catch (SquareApiException) {
            return null;
        }
    }

    /**
     * Invoice.order_id 経由で Square Payment ID を得る（Square 推奨経路）。
     */
    public function paymentIdFromInvoice(Invoice $invoice): ?string
    {
        $orderId = $invoice->getOrderId();
        if (! is_string($orderId) || $orderId === '') {
            return null;
        }

        return $this->paymentIdFromOrderId($orderId);
    }

    /**
     * Webhook 等の Invoice 配列から Payment ID を得る。payment_ids を優先し、なければ order_id 経由。
     *
     * @param  array<string, mixed>  $invoice
     */
    public function paymentIdFromInvoicePayload(array $invoice): ?string
    {
        $paymentId = $this->firstPaymentIdFromPaymentIds($invoice['payment_ids'] ?? null);
        if ($paymentId !== null) {
            return $paymentId;
        }

        $orderId = $invoice['order_id'] ?? null;
        if (! is_string($orderId) || $orderId === '') {
            return null;
        }

        return $this->paymentIdFromOrderId($orderId);
    }

    /**
     * @param  mixed  $paymentIds
     */
    public function firstPaymentIdFromPaymentIds(mixed $paymentIds): ?string
    {
        if (! is_array($paymentIds)) {
            return null;
        }

        foreach ($paymentIds as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Order ID から Payment ID を得る。RetrieveOrder 失敗・Tender なしは null。
     */
    public function paymentIdFromOrderId(string $orderId): ?string
    {
        try {
            $order = $this->retrieveOrder($orderId);
        } catch (SquareApiException) {
            return null;
        }

        return $this->paymentIdFromOrder($order);
    }

    /**
     * 期間終了時キャンセルをスケジュールする（Square CancelSubscription）。
     *
     * @throws SquareApiException
     */
    public function cancelSubscription(string $subscriptionId): Subscription
    {
        $response = $this->client()->subscriptions->cancel(
            new CancelSubscriptionsRequest(['subscriptionId' => $subscriptionId]),
        );
        $this->throwIfResponseHasErrors($response->getErrors(), 'cancelSubscription');

        return $this->requireSubscription($response->getSubscription(), 'cancelSubscription');
    }

    /**
     * 予約済みの解約（CANCEL アクション）を削除して、解約予定を取り消す。
     *
     * @throws SquareApiException
     */
    public function cancelScheduledCancellation(string $subscriptionId): Subscription
    {
        $response = $this->client()->subscriptions->get(
            new GetSubscriptionsRequest([
                'subscriptionId' => $subscriptionId,
                'include' => 'actions',
            ]),
        );
        $this->throwIfResponseHasErrors($response->getErrors(), 'retrieveSubscriptionWithActions');
        $subscription = $this->requireSubscription($response->getSubscription(), 'retrieveSubscriptionWithActions');

        $cancelActionId = null;
        foreach ($subscription->getActions() ?? [] as $action) {
            if ($action->getType() === 'CANCEL') {
                $cancelActionId = $action->getId();
                break;
            }
        }

        if ($cancelActionId === null || $cancelActionId === '') {
            throw new SquareApiException('No scheduled CANCEL action found', 422, null);
        }

        $deleteResponse = $this->client()->subscriptions->deleteAction(
            new DeleteActionSubscriptionsRequest([
                'subscriptionId' => $subscriptionId,
                'actionId' => $cancelActionId,
            ]),
        );
        $this->throwIfResponseHasErrors($deleteResponse->getErrors(), 'deleteSubscriptionAction');

        return $this->requireSubscription($deleteResponse->getSubscription(), 'deleteSubscriptionAction');
    }

    /**
     * Square Payments API で支払いを作成する。
     *
     * @param  array<string, mixed>  $params  sourceId, amount, currency, customerId, note, referenceId, locationId, idempotencyKey
     *
     * @throws SquareApiException 支払いが完了状態でないとき
     */
    public function createPayment(array $params): Payment
    {
        $locationId = $params['locationId'] ?? (string) config('services.square.location_id', '');
        if ($locationId === '') {
            throw new \RuntimeException('SQUARE_LOCATION_ID is not configured.');
        }

        $sourceId = isset($params['sourceId']) ? (string) $params['sourceId'] : '';
        if ($sourceId === '') {
            throw new \InvalidArgumentException('sourceId is required for createPayment.');
        }

        $amount = isset($params['amount']) ? (int) $params['amount'] : 0;
        if ($amount <= 0) {
            throw new \InvalidArgumentException('amount must be a positive integer for createPayment.');
        }

        $currency = isset($params['currency']) ? strtoupper((string) $params['currency']) : 'JPY';

        $request = new CreatePaymentRequest([
            'sourceId' => $sourceId,
            'idempotencyKey' => $params['idempotencyKey'] ?? Str::uuid()->toString(),
            'amountMoney' => new Money(['amount' => $amount, 'currency' => $currency]),
            'customerId' => isset($params['customerId']) ? (string) $params['customerId'] : null,
            'locationId' => $locationId,
            'note' => isset($params['note']) ? (string) $params['note'] : null,
            'referenceId' => isset($params['referenceId']) ? (string) $params['referenceId'] : null,
        ]);

        $response = $this->client()->payments->create($request);
        $this->throwIfResponseHasErrors($response->getErrors(), 'createPayment');

        $payment = $response->getPayment();
        if ($payment === null || $payment->getId() === null) {
            throw new SquareApiException('Square createPayment returned no payment', 502, null);
        }

        if (! $this->isPaymentCompleted($payment)) {
            throw new SquareApiException(
                'Square createPayment did not complete: ' . ($payment->getStatus() ?? 'unknown'),
                422,
                null,
            );
        }

        return $payment;
    }

    /**
     * Square Payments API で支払いを一覧取得する（customerId はクライアント側で絞り込み）。
     *
     * @param  array<string, mixed>  $params  customerId, since（Unix 秒）, limit, locationId
     * @return array<int, Payment>
     */
    public function listPayments(array $params): array
    {
        $customerId = isset($params['customerId']) ? (string) $params['customerId'] : '';
        $limit = isset($params['limit']) ? (int) $params['limit'] : 100;
        $limit = max(1, min(100, $limit));

        $beginTime = null;
        if (isset($params['since']) && is_numeric($params['since'])) {
            $beginTime = \Carbon\Carbon::createFromTimestamp(
                (int) $params['since'],
                config('app.timezone'),
            )->toIso8601String();
        }

        $locationId = $params['locationId'] ?? (string) config('services.square.location_id', '');

        $requestValues = [
            'beginTime' => $beginTime,
            'limit' => $limit,
        ];
        if ($locationId !== '') {
            $requestValues['locationId'] = $locationId;
        }

        $pager = $this->client()->payments->list(new ListPaymentsRequest($requestValues));

        $payments = [];
        foreach ($pager as $payment) {
            if ($customerId !== '' && $payment->getCustomerId() !== $customerId) {
                continue;
            }

            $payments[] = $payment;
            if (count($payments) >= $limit) {
                break;
            }
        }

        return $payments;
    }

    /**
     * 請求履歴に含める完了済み支払いかどうか。
     */
    public function isPaymentCompleted(Payment $payment): bool
    {
        $status = strtoupper((string) ($payment->getStatus() ?? ''));

        return $status === 'COMPLETED';
    }

    /**
     * Square API クライアントを返す。
     * @return \Square\SquareClient
     */
    private function client(): SquareClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $token = $this->accessToken ?? (string) config('services.square.access_token', '');
        if ($token === '') {
            throw new \RuntimeException('SQUARE_ACCESS_TOKEN is not configured.');
        }

        $baseUrl = $this->baseUrl ?? $this->defaultBaseUrl();
        $this->client = new SquareClient($token, null, ['baseUrl' => $baseUrl]);

        return $this->client;
    }

    /**
     * Square API のベース URL を返す。
     * @return string
     */
    private function defaultBaseUrl(): string
    {
        return config('app.env') === 'production'
            ? Environments::Production->value
            : Environments::Sandbox->value;
    }

    /**
     * Square API のエラーをスローする。
     * @param  array<\Square\Types\Error>|null  $errors
     * @param  string  $context
     * @return void
     */
    private function throwIfResponseHasErrors(?array $errors, string $context): void
    {
        if ($errors === null || $errors === []) {
            return;
        }

        $detail = $errors[0]->getDetail() ?? $errors[0]->getCode() ?? 'Unknown Square error';

        throw new SquareApiException(
            "Square {$context} failed: {$detail}",
            422,
            $detail,
        );
    }

    /**
     * ID のあるサブスクリプションを返す。無いときは例外にする。
     *
     * @throws SquareApiException サブスクリプションまたはその ID が無いとき
     */
    private function requireSubscription(?Subscription $subscription, string $context): Subscription
    {
        if ($subscription === null || $subscription->getId() === null) {
            throw new SquareApiException("Square {$context} returned no subscription", 502, null);
        }

        return $subscription;
    }
}
