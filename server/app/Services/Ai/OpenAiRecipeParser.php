<?php

namespace App\Services\Ai;

use App\Data\ParsedRecipe;
use App\Enums\HttpStatusCode;
use App\Interfaces\AiRecipeParserInterface;
use App\Traits\LoggingTrait;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * OpenAI Vision API を用いたレシピ画像パーサー。
 *
 * 画像は API 呼び出しのみに使用し、サーバーには保存しない（著作権配慮）。
 */
class OpenAiRecipeParser implements AiRecipeParserInterface
{
    use LoggingTrait;

    /** @see config('services.ai.vision_provider') が openai のときに DI される */
    private string $model;

    public function __construct()
    {
        $this->model = config('ai.models.vision');
    }

    /**
     * base64 エンコード済み画像からレシピ情報を解析する。
     *
     * @param  string  $base64Image  MIME プレフィックスなしの base64 文字列
     *
     * @throws HttpException API 失敗時は 502、画像不正時は 400
     */
    public function parseImage(string $base64Image): ParsedRecipe
    {
        $mimeType = $this->detectMimeType($base64Image);
        $dataUri = "data:{$mimeType};base64,{$base64Image}";

        try {
            $response = OpenAI::chat()->create([
                'model' => $this->model,
                // プロンプトで指定した JSON スキーマどおりに返させる
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'この画像からレシピ情報を読み取り、指定の JSON 形式で返してください。',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $dataUri,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            $this->logWarning(
                __METHOD__,
                'ai.recipe.parse',
                'OpenAI Vision API call failed.',
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

        $content = $response->choices[0]->message->content ?? null;

        if (! is_string($content) || $content === '') {
            $this->logWarning(
                __METHOD__,
                'ai.recipe.parse',
                'OpenAI Vision API returned empty content.',
            );

            throw new HttpException(
                HttpStatusCode::BAD_GATEWAY->value,
                __('api.general.server_error'),
            );
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->logWarning(
                __METHOD__,
                'ai.recipe.parse',
                'Failed to decode OpenAI JSON response.',
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

        if (! is_array($decoded)) {
            throw new HttpException(
                HttpStatusCode::BAD_GATEWAY->value,
                __('api.general.server_error'),
            );
        }

        return ParsedRecipe::fromArray($decoded);
    }

    /**
     * base64 デコード後のバイナリから MIME タイプを検出する。
     *
     * MIMEタイプの許可チェックは FormRequest で実施済みのため、
     * ここでは data URI 組み立てに必要な MIME タイプ文字列の取得のみ行う。
     */
    private function detectMimeType(string $base64Image): string
    {
        $binary = base64_decode($base64Image, true);

        if ($binary === false) {
            throw new HttpException(
                HttpStatusCode::BAD_REQUEST->value,
                __('api.general.validation_error'),
            );
        }

        return (new finfo(FILEINFO_MIME_TYPE))->buffer($binary);
    }

    /**
     * Vision API へ渡す JSON 出力スキーマ。
     *
     * {@see ParsedRecipe::fromArray} およびクライアント側フォーム投入形式と一致させる。
     */
    private function systemPrompt(): string
    {
        return <<<'PROMPT'
あなたは料理レシピ画像を解析するアシスタントです。画像から読み取れるレシピ情報を、次の JSON スキーマに従って返してください。

{
  "name": "料理名（string）",
  "servingCount": 人数（integer または null）,
  "url": "URL（string、なければ空文字）",
  "ingredients": [
    {
      "name": "材料名（string）",
      "quantity": 数量（number または null）,
      "unitName": "単位名（string、例: g, ml, 個, 大さじ）",
      "categoryName": "材料カテゴリ（string、例: 野菜, 肉, 調味料。不明なら空文字）"
    }
  ],
  "steps": [
    {
      "instruction": "調理手順（string）"
    }
  ]
}

不明な項目は null または空文字にしてください。JSON オブジェクトのみを返してください。
PROMPT;
    }
}
