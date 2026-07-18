<?php

use App\Enums\HttpStatusCode;
use App\Services\Ai\GoogleVisionRecipeOcr;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

uses(TestCase::class);

const GOOGLE_VISION_TEST_ENDPOINT = 'https://vision.googleapis.com/v1/images:annotate';
const GOOGLE_VISION_TEST_API_KEY = 'test-api-key';

function makeGoogleVisionRecipeOcr(): GoogleVisionRecipeOcr
{
    config([
        'services.google_cloud_vision.api_key' => GOOGLE_VISION_TEST_API_KEY,
        'services.google_cloud_vision.endpoint' => GOOGLE_VISION_TEST_ENDPOINT,
    ]);

    return new GoogleVisionRecipeOcr();
}

function googleVisionRequestUrl(): string
{
    return GOOGLE_VISION_TEST_ENDPOINT . '?key=' . urlencode(GOOGLE_VISION_TEST_API_KEY);
}

function expectGoogleVisionServerError(callable $callback): void
{
    try {
        $callback();
        test()->fail('Expected HttpException was not thrown.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(HttpStatusCode::BAD_GATEWAY->value);
        expect($e->getMessage())->toBe(__('api.general.server_error'));
    }
}

// ===== extract() メソッドのテストケース =====

test('4-5-1: 【extract】 API レスポンスから OCR テキストを返す', function () {
    $base64Image = base64_encode('recipe-image-bytes');
    $expectedText = "照り焼きチキン\n玉ねぎ 1個\n1. 鶏もも肉を焼く";

    Http::fake([
        googleVisionRequestUrl() => Http::response([
            'responses' => [
                [
                    'fullTextAnnotation' => [
                        'text' => $expectedText,
                    ],
                ],
            ],
        ], 200),
    ]);

    $ocr = makeGoogleVisionRecipeOcr();
    $text = $ocr->extract($base64Image, 'image/jpeg');

    expect($text)->toBe($expectedText);
});

test('4-5-2: 【extract】 DOCUMENT_TEXT_DETECTION と languageHints を指定して Vision API を呼び出す', function () {
    $base64Image = base64_encode('recipe-image-bytes');

    Http::fake([
        googleVisionRequestUrl() => Http::response([
            'responses' => [
                [
                    'fullTextAnnotation' => [
                        'text' => 'OCR result',
                    ],
                ],
            ],
        ], 200),
    ]);

    $ocr = makeGoogleVisionRecipeOcr();
    $ocr->extract($base64Image, 'image/png');

    Http::assertSent(function ($request) use ($base64Image) {
        $body = $request->data();

        return $request->url() === googleVisionRequestUrl()
            && $request->method() === 'POST'
            && data_get($body, 'requests.0.image.content') === $base64Image
            && data_get($body, 'requests.0.features.0.type') === 'DOCUMENT_TEXT_DETECTION'
            && data_get($body, 'requests.0.imageContext.languageHints') === ['ja'];
    });
});

test('4-5-3: 【extract】 API キー未設定時は 502 を投げる', function () {
    config([
        'services.google_cloud_vision.api_key' => '',
        'services.google_cloud_vision.endpoint' => GOOGLE_VISION_TEST_ENDPOINT,
    ]);

    $ocr = new GoogleVisionRecipeOcr();

    expectGoogleVisionServerError(
        fn () => $ocr->extract(base64_encode('recipe-image-bytes'), 'image/jpeg'),
    );

    Http::assertNothingSent();
});

test('4-5-4: 【extract】 HTTP 通信失敗時は 502 を投げる', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    $ocr = makeGoogleVisionRecipeOcr();

    expectGoogleVisionServerError(
        fn () => $ocr->extract(base64_encode('recipe-image-bytes'), 'image/jpeg'),
    );
});

test('4-5-5: 【extract】 Vision API が HTTP エラーを返した場合は 502 を投げる', function () {
    Http::fake([
        googleVisionRequestUrl() => Http::response([
            'error' => [
                'message' => 'Internal error',
            ],
        ], 500),
    ]);

    $ocr = makeGoogleVisionRecipeOcr();

    expectGoogleVisionServerError(
        fn () => $ocr->extract(base64_encode('recipe-image-bytes'), 'image/jpeg'),
    );
});

test('4-5-6: 【extract】 レスポンスのトップレベル error.message がある場合は 502 を投げる', function () {
    Http::fake([
        googleVisionRequestUrl() => Http::response([
            'error' => [
                'message' => 'API key not valid. Please pass a valid API key.',
            ],
        ], 200),
    ]);

    $ocr = makeGoogleVisionRecipeOcr();

    expectGoogleVisionServerError(
        fn () => $ocr->extract(base64_encode('recipe-image-bytes'), 'image/jpeg'),
    );
});

test('4-5-7: 【extract】 responses.0.error.message がある場合は 502 を投げる', function () {
    Http::fake([
        googleVisionRequestUrl() => Http::response([
            'responses' => [
                [
                    'error' => [
                        'message' => 'Bad image data.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $ocr = makeGoogleVisionRecipeOcr();

    expectGoogleVisionServerError(
        fn () => $ocr->extract(base64_encode('recipe-image-bytes'), 'image/jpeg'),
    );
});

test('4-5-8: 【extract】 fullTextAnnotation.text が空の場合は 502 を投げる', function () {
    Http::fake([
        googleVisionRequestUrl() => Http::response([
            'responses' => [
                [
                    'fullTextAnnotation' => [
                        'text' => '',
                    ],
                ],
            ],
        ], 200),
    ]);

    $ocr = makeGoogleVisionRecipeOcr();

    expectGoogleVisionServerError(
        fn () => $ocr->extract(base64_encode('recipe-image-bytes'), 'image/jpeg'),
    );
});

test('4-5-9: 【extract】 fullTextAnnotation.text が存在しない場合は 502 を投げる', function () {
    Http::fake([
        googleVisionRequestUrl() => Http::response([
            'responses' => [
                [],
            ],
        ], 200),
    ]);

    $ocr = makeGoogleVisionRecipeOcr();

    expectGoogleVisionServerError(
        fn () => $ocr->extract(base64_encode('recipe-image-bytes'), 'image/jpeg'),
    );
});
