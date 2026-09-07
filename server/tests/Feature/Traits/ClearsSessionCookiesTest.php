<?php

use App\Traits\ClearsSessionCookies;
use Illuminate\Http\JsonResponse;

beforeEach(function () {
    $this->dummy = new class {
        use ClearsSessionCookies;

        public function testClearSessionCookiesOnResponse(JsonResponse $response): JsonResponse
        {
            return $this->clearSessionCookiesOnResponse($response);
        }
    };
});

// ===== clearSessionCookiesOnResponse() メソッドのテストケース =====

test('1-5-1: 【clearSessionCookiesOnResponse】 レスポンスにセッション・XSRF-TOKEN削除用Cookieが付与される', function () {
    $response = new JsonResponse(['success' => true]);
    $response = $this->dummy->testClearSessionCookiesOnResponse($response);

    $cookies = $response->headers->getCookies();
    expect($cookies)->not->toBeEmpty();

    $sessionCookieName = config('session.cookie');
    $sessionCookies = array_filter($cookies, fn ($c) => $c->getName() === $sessionCookieName);
    $xsrfCookies = array_filter($cookies, fn ($c) => $c->getName() === 'XSRF-TOKEN');

    expect($sessionCookies)->not->toBeEmpty();
    expect($xsrfCookies)->not->toBeEmpty();

    foreach (array_merge($sessionCookies, $xsrfCookies) as $cookie) {
        expect($cookie->getValue())->toBe('');
    }
});
