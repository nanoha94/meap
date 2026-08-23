<?php

use App\Providers\AppServiceProvider;

// ===== boot() メソッドのテストケース =====

test('6-1-1: 【Webhook設定検証】 STRIPE_WEBHOOK_SECRET 未設定時は起動時に例外', function () {
    app()->detectEnvironment(fn () => 'staging');
    config(['cashier.webhook.secret' => null]);

    $provider = new AppServiceProvider(app());

    expect(fn () => $provider->boot())
        ->toThrow(RuntimeException::class, 'STRIPE_WEBHOOK_SECRET must be set.');
});

test('6-1-2: 【Webhook設定検証】 STRIPE_WEBHOOK_SECRET 設定済みなら起動できる', function () {
    config(['cashier.webhook.secret' => 'whsec_test_secret']);

    $provider = new AppServiceProvider(app());
    $provider->boot();

    expect(true)->toBeTrue();
});
