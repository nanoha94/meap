<?php

use App\Providers\AppServiceProvider;

// ===== boot() メソッドのテストケース =====

test('6-1-1: 【起動】 boot が例外なく完了する', function () {
    $provider = new AppServiceProvider(app());
    $provider->boot();

    expect(true)->toBeTrue();
});
