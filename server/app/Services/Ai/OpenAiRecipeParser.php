<?php

namespace App\Services\Ai;

use App\Data\ParsedRecipe;
use App\Enums\HttpStatusCode;
use App\Interfaces\AiRecipeParserInterface;
use App\Traits\LoggingTrait;
use OpenAI\Laravel\Facades\OpenAI;
use finfo;
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
                __('operations.ai.recipe.parse'),
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
                __('operations.ai.recipe.parse'),
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

unitName のルール:
- unitName は次の利用可能な単位リストから選ぶこと: g, ml, cc, カップ, 個, 枚, 本, 片, 粒, 房, 束, 袋, 缶, 丁, 合, 杯, 切れ, パック, セット, ケース, cm, 滴, L, 大さじ, 小さじ, 少々, ひとつまみ, ふたつまみ, 適量, お好み
- リストにない単位は、最も近い単位を選ぶ

括弧付き併記ルール（重要）:
- 「1枚(250g)」「1枚（250g）」「1/2本(85g)」のように、括弧の外側に数量・単位、括弧の内側に別の数量・単位が併記されている場合は、括弧の外側を優先して quantity / unitName に採用すること
  - quantity = 括弧外の数量（1 → 1、1/2 → 0.5）
  - unitName = 括弧外の単位（枚, 本, 個 など）
  - 括弧内（250g, 85ml など）は補足情報として無視し、主単位にしない
- 括弧内が g / ml 以外（例: 約250g、500ml 相当）であっても同様に、括弧外を優先する
- 括弧のない表記（20g など）は従来どおり

prefix 単位ルール（大さじ・小さじ、重要）:
- 大さじ・小さじは単位名が数量の前に付く（例: 大さじ1、大さじ1/2）。各行は独立して読み取り、隣の行の分数（/2 など）を混ぜないこと
- 大さじ1 は整数 1 であり、1/2 ではない。大さじ1 と 大さじ1/2 が並ぶ場合はそれぞれ別の材料行として正しく区別すること
- quantityDisplay には数値部分のみを返す（大さじ・小さじの単位名は含めない）
  - 大さじ1 → quantity=1, unitName=大さじ, quantityDisplay="1"
  - 大さじ1/2 → quantity=0.5, unitName=大さじ, quantityDisplay="1/2"
  - 大さじ1 1/2 → quantity=1.5, unitName=大さじ, quantityDisplay="1 1/2"（画像が半角スペース区切りの場合）
  - 大さじ1と1/2 → quantity=1.5, unitName=大さじ, quantityDisplay="1と1/2"（画像が「と」区切りの場合）
- 小さじも同様（小さじ1 → quantity=1, quantityDisplay="1" など）

帯分数の区切りルール（重要）:
- 帯分数（1 1/2 や 1と1/2）は、画像に書かれている区切りを quantityDisplay にそのまま反映すること
  - 画像が「1 1/2」「大さじ1 1/2」→ quantityDisplay="1 1/2"（半角スペース区切り）
  - 画像が「1と1/2」「大さじ1と1/2」→ quantityDisplay="1と1/2"（「と」区切り）
- 「と」区切りとスペース区切りを勝手に統一しない。画像の表記に従うこと

quantityDisplay のルール:
- 数量の数字・スラッシュ・スペースは可能なら半角で返すこと（全角で返ってもサーバー側で半角化される）
- 画像に書かれている数量の数値部分を返す（例: 分数 "1/2", "1 1/2", "1と1/2"、小数 "0.5", "1.5"、整数 "200"）
- 括弧付き併記では括弧外の数量表記のみを返す（例: 1枚(250g) → "1"、1/2本(85g) → "1/2"）。括弧内は含めない
- prefix 単位（大さじ・小さじ）では単位名を quantityDisplay に含めない（"大さじ1/2" ではなく "1/2"）
- 数量がない場合（適量・少々など）は null
- quantity（数値）は計算用。quantityDisplay と矛盾しないこと
- quantityDisplay を省略した場合、サーバー側で quantity から表示表記を補完する。可能な限り両方を返すこと

具体例:
| 画像表記 | quantity | unitName | quantityDisplay |
| 1枚(250g) | 1 | 枚 | 1 |
| 1枚（250g） | 1 | 枚 | 1 |
| 1/2本(85g) | 0.5 | 本 | 1/2 |
| 20g | 20 | g | 20 |
| 大さじ1 | 1 | 大さじ | 1 |
| 大さじ1/2 | 0.5 | 大さじ | 1/2 |
| 大さじ1 1/2 | 1.5 | 大さじ | 1 1/2 |
| 大さじ1と1/2 | 1.5 | 大さじ | 1と1/2 |
| 少々 | null | 少々 | null |

不明な項目は null または空文字にしてください。JSON オブジェクトのみを返してください。
PROMPT;
    }
}
