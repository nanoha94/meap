<?php

namespace App\Services\Ai;

use App\Enums\HttpStatusCode;
use App\Interfaces\RecipeOcrInterface;
use App\Traits\LoggingTrait;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Google Cloud Vision API を用いたレシピ画像 OCR。
 *
 * 画像は API 呼び出しのみに使用し、サーバーには保存しない（著作権配慮）。
 */
class GoogleVisionRecipeOcr implements RecipeOcrInterface
{
    use LoggingTrait;

    private string $apiKey;

    private string $endpoint;

    public function __construct()
    {
        $this->apiKey = (string) config('services.google_cloud_vision.api_key');
        $this->endpoint = (string) config('services.google_cloud_vision.endpoint');
    }

    /**
     * {@inheritDoc}
     */
    public function extract(string $base64Image, string $mimeType): string
    {
        if ($this->apiKey === '') {
            $this->logWarning(
                __METHOD__,
                __('operations.ai.recipe.parse_img'),
                'Google Cloud Vision API key is not configured.',
            );

            throw new HttpException(
                HttpStatusCode::BAD_GATEWAY->value,
                __('api.general.server_error'),
            );
        }

        $url = $this->endpoint . '?key=' . urlencode($this->apiKey);

        try {
            $response = Http::timeout(60)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'requests' => [
                        [
                            'image' => [
                                'content' => $base64Image,
                            ],
                            'features' => [
                                [
                                    'type' => 'DOCUMENT_TEXT_DETECTION',
                                ],
                            ],
                            'imageContext' => [
                                'languageHints' => ['ja'],
                            ],
                        ],
                    ],
                ]);
        } catch (Throwable $e) {
            $this->logWarning(
                __METHOD__,
                __('operations.ai.recipe.parse_img'),
                'Google Cloud Vision OCR failed.',
                [
                    'exception_message' => $e->getMessage(),
                ],
            );

            throw new HttpException(
                HttpStatusCode::BAD_GATEWAY->value,
                __('api.general.server_error'),
                $e,
            );
        }

        if (! $response->successful()) {
            $this->logWarning(
                __METHOD__,
                __('operations.ai.recipe.parse_img'),
                'Google Cloud Vision API returned HTTP error.',
                [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ],
            );

            throw new HttpException(
                HttpStatusCode::BAD_GATEWAY->value,
                __('api.general.server_error'),
            );
        }

        $body = $response->json();

        $apiError = data_get($body, 'error.message')
            ?? data_get($body, 'responses.0.error.message');

        if (is_string($apiError) && $apiError !== '') {
            $this->logWarning(
                __METHOD__,
                __('operations.ai.recipe.parse_img'),
                'Google Cloud Vision API returned an error response.',
                [
                    'error' => $apiError,
                ],
            );

            throw new HttpException(
                HttpStatusCode::BAD_GATEWAY->value,
                __('api.general.server_error'),
            );
        }

        $text = data_get($body, 'responses.0.fullTextAnnotation.text');

        if (! is_string($text) || $text === '') {
            $this->logWarning(
                __METHOD__,
                __('operations.ai.recipe.parse_img'),
                'Google Cloud Vision API returned empty content.',
            );

            throw new HttpException(
                HttpStatusCode::BAD_GATEWAY->value,
                __('api.general.server_error'),
            );
        }

        return $text;
    }
}
