<?php

use App\Helpers\SafeUrlFetcher;
use App\Exceptions\SafeUrlFetchException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

// ===== isBlockedIp() メソッドのテストケース =====

test('5-2-1: 【isBlockedIp】 private / loopback / link-local IP を拒否する', function (string $ip) {
    expect(SafeUrlFetcher::isBlockedIp($ip))->toBeTrue();
})->with([
    '127.0.0.1',
    '10.0.0.1',
    '192.168.1.1',
    '169.254.169.254',
    '::1',
]);

test('5-2-2: 【isBlockedIp】 公開 IP は許可する', function (string $ip) {
    expect(SafeUrlFetcher::isBlockedIp($ip))->toBeFalse();
})->with([
    '8.8.8.8',
    '1.1.1.1',
]);

// ===== validateUrl() メソッドのテストケース =====

test('5-2-3: 【validateUrl】 http スキームを拒否する', function () {
    expect(SafeUrlFetcher::validateUrl('http://example.com/recipe'))
        ->toBe('urlはhttpsで始まるURLを指定してください。');
});

test('5-2-4: 【validateUrl】 localhost を拒否する', function () {
    expect(SafeUrlFetcher::validateUrl('https://localhost/recipe'))
        ->toBe('指定されたURLにはアクセスできません。');
});

test('5-2-5: 【validateUrl】 メタデータ IP を拒否する', function () {
    expect(SafeUrlFetcher::validateUrl('https://169.254.169.254/meta-data'))
        ->toBe('指定されたURLにはアクセスできません。');
});

test('5-2-6: 【validateUrl】 公開 HTTPS URL は許可する', function () {
    expect(SafeUrlFetcher::validateUrl('https://example.com/recipe'))->toBeNull();
});

// ===== fetch() メソッドのテストケース =====

test('5-2-7: 【fetch】 リダイレクトレスポンスを拒否する', function () {
    Http::fake([
        'https://example.com/recipe' => Http::response('', 302, [
            'Location' => 'https://example.com/other',
        ]),
    ]);

    try {
        SafeUrlFetcher::fetch('https://example.com/recipe');
    } catch (SafeUrlFetchException $e) {
        expect($e->type)->toBe(SafeUrlFetchException::TYPE_RESPONSE);
        expect($e->httpStatus)->toBe(302);

        throw $e;
    }
})->throws(SafeUrlFetchException::class);

test('5-2-8: 【fetch】 正常レスポンスのボディを返す', function () {
    Http::fake([
        'https://example.com/recipe' => Http::response('<html><body>recipe</body></html>', 200),
    ]);

    $body = SafeUrlFetcher::fetch('https://example.com/recipe');

    expect($body)->toBe('<html><body>recipe</body></html>');
});
