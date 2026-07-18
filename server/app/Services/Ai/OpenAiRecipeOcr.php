<?php

namespace App\Services\Ai;

use App\Enums\HttpStatusCode;
use App\Interfaces\RecipeOcrInterface;
use App\Traits\LoggingTrait;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * OpenAI Vision API を用いたレシピ画像 OCR。
 *
 * 画像は API 呼び出しのみに使用し、サーバーには保存しない（著作権配慮）。
 */
class OpenAiRecipeOcr implements RecipeOcrInterface
{
    use LoggingTrait;

    private string $ocrModel;

    public function __construct()
    {
        $this->ocrModel = config('ai.models.ocr');
    }

    /**
     * {@inheritDoc}
     */
    public function extract(string $base64Image, string $mimeType): string
    {
        $dataUri = "data:{$mimeType};base64,{$base64Image}";

        return $this->requestTextCompletion(
            model: $this->ocrModel,
            messages: [
                [
                    'role' => 'system',
                    'content' => $this->ocrSystemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'この画像内のレシピ情報（料理名・材料見出し・材料行・手順）を、上から順にすべて書き写してください。数量は各行に付いている文字だけを写し、上下の行と取り違えないでください。手順テキストは意味を理解して書き直すのではなく、画像の文字を1文字ずつそのまま転記してください。',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $dataUri,
                                'detail' => 'high',
                            ],
                        ],
                    ],
                ],
            ],
            failureContext: 'OpenAI Vision OCR failed.',
        );
    }

    /**
     * OpenAI API を呼び出してプレーンテキストのレスポンスを取得する。
     *
     * @param  string  $model
     * @param  list<array<string, mixed>>  $messages
     * @param  string  $failureContext
     */
    private function requestTextCompletion(string $model, array $messages, string $failureContext): string
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => $model,
                'temperature' => 0, // 確定的な回答を生成するために0に設定
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

        return $content;
    }

    /**
     * 画像 OCR 用プロンプト。
     */
    private function ocrSystemPrompt(): string
    {
        return <<<'PROMPT'
あなたはレシピ画像の OCR アシスタントです。
画像に見える文字を、上から順にそのまま書き写してください。

出力の構成（この順序で書く）:
1. 料理名 — 画像上部のタイトル・見出しをそのまま写す
2. 材料見出し — 「材料【2人分】」などの見出し行をそのまま写す
3. 材料行 — 各行を1行ずつ写す
4. 手順 — 番号付きの調理手順テキストを1手順ずつ写す

全体ルール:
- 推測・補正・要約・省略はしない
- 画像にある情報はすべて写す（料理名・見出し・材料・手順）
- 評価（星）・カロリー・栄養素・URL・広告などレシピ本文以外は省略する

材料行のルール:
- 各行は画像の文字をできるだけそのまま写す（例: 「しょうゆ 大さじ1と1/2」なら「と」を省略しない）
- 各行は「材料名」と「その行の右側（または同じ行内）の数量・単位」を1セットとして写す
- 上下の行の数量・単位を取り違えない（例: 「酒 大さじ1」と「砂糖 大さじ1/2」は別行のまま写す。入れ替えない）
- 調味料が連続する行（酒・みりん・砂糖・しょうゆ等）は、数量がどの材料名の行に付いているか、画像上の位置関係を確認してから写す
- 「1」「1/2」「1と1/2」は別表記。隣行の数量と混同しない
- 数量の推測・丸め・行の統合・行の入れ替えはしない

手順のルール:
- 手順テキストは「意味を読み取って書く」のではなく、画像上の文字を1文字ずつ読み取って転記する
- 動詞・助詞・文末表現を自分の言葉に置き換えない（例: 画像が「半分に切る」なら「半月に切る」と書かない。画像が「並べる」なら「入れ」に変えない）
- 料理の一般知識で補完・修正しない。画像にない語句を足さず、画像にある語句を別の表現にしない
- 長い手順でも途中で切らず最後まで写す
- 手順の番号は省略してよい
- 手順に添えられた写真・動画の説明は不要

プレーンテキストのみを返してください。
PROMPT;
    }
}
