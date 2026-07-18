<?php

namespace App\Services\Ai;

use App\Data\ParsedRecipe;
use App\Enums\HttpStatusCode;
use App\Interfaces\AiRecipeParserInterface;
use App\Interfaces\RecipeOcrInterface;
use App\Traits\LoggingTrait;
use OpenAI\Laravel\Facades\OpenAI;
use finfo;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * レシピ画像パーサー。
 *
 * 1. {@see RecipeOcrInterface} で画像からテキストを OCR 抽出
 * 2. OpenAI で抽出テキストを JSON に構造化
 *
 * 画像は OCR API 呼び出しのみに使用し、サーバーには保存しない（著作権配慮）。
 */
class OpenAiRecipeParser implements AiRecipeParserInterface
{
    use LoggingTrait;

    private string $textModel;

    public function __construct(
        private readonly RecipeOcrInterface $recipeOcr,
    ) {
        $this->textModel = config('ai.models.text');
    }

    /**
     * base64 エンコード済み画像からレシピ情報を解析する。
     *
     * @param  string  $base64Image  MIME プレフィックスなしの base64 文字列
     * @param  list<string>  $unitNames  グループの単位マスタ名一覧
     *
     * @throws HttpException API 失敗時は 502、画像不正時は 400
     */
    public function parseImage(string $base64Image, array $unitNames): ParsedRecipe
    {
        $mimeType = $this->detectMimeType($base64Image);
        $ocrText = $this->extractTextFromImage($base64Image, $mimeType);

        return $this->structureRecipeFromText($ocrText, $unitNames);
    }

    /**
     * 画像からレシピのテキストを OCR 抽出する（第1段階）。
     */
    private function extractTextFromImage(string $base64Image, string $mimeType): string
    {
        return $this->recipeOcr->extract($base64Image, $mimeType);
    }

    /**
     * OCR 抽出テキストをレシピ JSON に構造化する（第2段階）。
     *
     * @param  list<string>  $unitNames
     */
    private function structureRecipeFromText(string $ocrText, array $unitNames): ParsedRecipe
    {
        $decoded = $this->requestJsonCompletion(
            model: $this->textModel,
            messages: [
                [
                    'role' => 'system',
                    'content' => $this->structureSystemPrompt($unitNames),
                ],
                [
                    'role' => 'user',
                    'content' => <<<USER
以下はレシピ画像から書き写したテキストです。このテキストだけを根拠に、指定の JSON 形式へ構造化してください。テキストにない情報は推測しないでください。

{$ocrText}
USER,
                ],
            ],
            failureContext: 'OpenAI recipe structuring failed.',
        );

        return ParsedRecipe::fromArray($decoded);
    }

    /**
     * OpenAI API を呼び出して JSON 形式のレスポンスを取得する。
     *
     * @param  string  $model
     * @param  list<array<string, mixed>>  $messages
     * @param  string  $failureContext
     * @return array<string, mixed>
     */
    private function requestJsonCompletion(string $model, array $messages, string $failureContext): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0,
                'messages' => $messages,
            ]);
        } catch (Throwable $e) {
            $this->logWarning(
                __METHOD__,
                __('operations.ai.recipe.parse'),
                $failureContext,
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
                __('operations.ai.recipe.parse'),
                'OpenAI API returned empty content.',
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
                __('operations.ai.recipe.parse'),
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

        return $decoded;
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
     * 第2段階: OCR テキストを {@see ParsedRecipe} 形式へ構造化するプロンプト。
     *
     * @param  list<string>  $unitNames
     */
    private function structureSystemPrompt(array $unitNames): string
    {
        $unitList = implode(', ', $unitNames);

        return <<<PROMPT
あなたは OCR 抽出済みのレシピテキストを構造化するアシスタントです。入力テキストだけを根拠に、次の JSON スキーマで返してください。

{
  "name": "料理名（string）",
  "servingCount": 人数（integer または null）,
  "ingredients": [
    {
      "name": "材料名（string）",
      "quantity": 数量（number または null。計算用）,
      "quantityDisplay": "数量の表示表記（string または null）",
      "unitName": "単位名（string）",
      "categoryName": "材料カテゴリ（string、例: 野菜, 肉, 調味料。不明なら空文字）"
    }
  ],
  "steps": [
    {
      "instruction": "調理手順（string）"
    }
  ]
}

servingCount のルール（重要）:
- 材料セクション見出しの「N人分」表記からのみ人数を取る（例: 「材料 【2人分】」→ 2）
- 栄養成分・調理時間・コメント・手順内の数字から servingCount を推測しない

材料行の扱い（重要）:
- 材料セクションを上から順に処理し、材料行のみを ingredients に変換する
- 調味料 などの見出し行は ingredients に含めない（以降の categoryName 判定の参考にはしてよい）
- 1行=1材料。別の行の数量を入れ替えたり流用したりしない
- 各行の name / quantity / unitName / quantityDisplay は、その行のテキストだけから取る

数量の読み取り手順:
1. まず各行から quantityDisplay に写す数値部分を確定する
2. quantity は quantityDisplay を数値化した結果のみを入れる
3. quantity と quantityDisplay は必ず一致させる

unitName のルール:
- unitName は次から選ぶ: {$unitList}
- リストにない単位は、最も近い単位を選ぶ

括弧付き併記:
- 「1枚(250g)」は括弧外を優先（quantityDisplay="1", unitName=枚）

prefix 単位（大さじ・小さじ）:
- quantityDisplay には数値部分のみ（"1/2", "1と1/2" など。単位名は含めない）
- 「1/2」と「1と1/2」は別表記。「と」を省略しない

手順セクション:
- 上から順に steps[].instruction へ

不明な項目は null または空文字。JSON オブジェクトのみを返してください。
PROMPT;
    }
}
