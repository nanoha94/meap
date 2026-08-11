<?php

namespace App\Services\Ai;

use App\Data\ParsedRecipe;
use App\Enums\HttpStatusCode;
use App\Exceptions\SafeUrlFetchException;
use App\Helpers\SafeUrlFetcher;
use App\Interfaces\AiRecipeParserInterface;
use App\Interfaces\RecipeOcrInterface;
use App\Traits\LoggingTrait;
use DOMDocument;
use OpenAI\Laravel\Facades\OpenAI;
use finfo;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * レシピ画像・URL パーサー。
 *
 * 画像: {@see RecipeOcrInterface} で OCR 抽出 → OpenAI で JSON 構造化
 * URL: HTML 取得 → テキスト抽出 → OpenAI で JSON 構造化
 *
 * 画像・HTML は解析のみに使用し、サーバーには保存しない（著作権配慮）。
 */
class OpenAiRecipeParser implements AiRecipeParserInterface
{
    use LoggingTrait;

    private const HTTP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /** HTML レスポンスの最大バイト数（5MB） */
    private const MAX_HTML_BYTES = 5 * 1024 * 1024;

    /** OpenAI 構造化に渡す抽出テキストの最大文字数（トークン制限考慮） */
    private const MAX_EXTRACTED_TEXT_LENGTH = 50000;

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
     * @param  list<string>  $categoryNames  グループの材料カテゴリーマスタ名一覧
     *
     * @throws HttpException API 失敗時は 502、画像不正時は 400
     */
    public function parseImage(string $base64Image, array $unitNames, array $categoryNames = []): ParsedRecipe
    {
        $mimeType = $this->detectMimeType($base64Image);
        $ocrText = $this->recipeOcr->extract($base64Image, $mimeType);
        $ocrText = preg_replace(
            '/酉(?=[\s　]*(?:大さじ|小さじ|大[１1]|小[１1]|適量|\d))/u',
            '酒',
            $ocrText,
        ) ?? $ocrText;

        $this->logRecipeParseDiagnostic('ocr', [
            'ocr_provider' => config('services.ai.ocr_provider'),
            'ocr_model' => config('ai.models.ocr'),
            'ocr_text' => $ocrText,
        ]);

        return $this->structureRecipeFromText(
            $ocrText,
            $unitNames,
            $categoryNames,
            '以下はレシピ画像から書き写したテキストです。',
        );
    }

    /**
     * URL 先の Web ページからレシピ情報を解析する。
     *
     * @param  list<string>  $unitNames
     * @param  list<string>  $categoryNames  グループの材料カテゴリーマスタ名一覧
     *
     * @throws HttpException HTML 取得失敗時は 502、抽出テキスト空時は 400
     */
    public function parseUrl(string $url, array $unitNames, array $categoryNames = []): ParsedRecipe
    {
        $html = $this->fetchHtmlFromUrl($url);
        $pageText = $this->extractTextFromHtml($html);
        $pageText = $this->truncateTextForTokenLimit($pageText);

        if ($pageText === '') {
            throw new HttpException(
                HttpStatusCode::BAD_REQUEST->value,
                __('api.general.validation_error'),
            );
        }

        return $this->structureRecipeFromText(
            $pageText,
            $unitNames,
            $categoryNames,
            '以下はレシピWebページから抽出したテキストです。',
        );
    }

    /**
     * URL 先の HTML を HTTP GET で取得する。
     *
     * @throws HttpException
     */
    private function fetchHtmlFromUrl(string $url): string
    {
        try {
            return SafeUrlFetcher::fetch(
                $url,
                timeoutSeconds: 30,
                headers: ['User-Agent' => self::HTTP_USER_AGENT],
                maxBytes: self::MAX_HTML_BYTES,
            );
        } catch (SafeUrlFetchException $e) {
            if ($e->type === SafeUrlFetchException::TYPE_VALIDATION) {
                throw new HttpException(
                    HttpStatusCode::BAD_REQUEST->value,
                    __('api.general.validation_error'),
                );
            }

            $logContext = [
                'url' => $url,
                'reason' => $e->type,
                'exception_message' => $e->getPrevious()?->getMessage(),
            ];

            if ($e->httpStatus !== null) {
                $logContext['status'] = $e->httpStatus;
            }

            if ($e->bodyLength !== null) {
                $logContext['body_length'] = $e->bodyLength;
            }

            $this->logWarning(
                __('operations.ai.recipe.parse_url'),
                match ($e->type) {
                    SafeUrlFetchException::TYPE_REQUEST => __('operations.ai.recipe.url_fetch_failed'),
                    SafeUrlFetchException::TYPE_RESPONSE => __('operations.ai.recipe.url_fetch_non_success'),
                    default => __('operations.ai.recipe.url_fetch_invalid_body'),
                },
                $logContext,
                __METHOD__,
            );

            throw new HttpException(
                HttpStatusCode::BAD_GATEWAY->value,
                __('api.general.server_error'),
                $e,
            );
        }
    }

    /**
     * HTML から script / style を除去し、可視テキストを抽出する。
     */
    private function extractTextFromHtml(string $html): string
    {
        $dom = new DOMDocument();
        $previousLibxmlSetting = libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);

        foreach (['script', 'style', 'noscript'] as $tagName) {
            $elements = $dom->getElementsByTagName($tagName);

            for ($index = $elements->length - 1; $index >= 0; $index--) {
                $element = $elements->item($index);
                $element?->parentNode?->removeChild($element);
            }
        }

        $text = $dom->textContent ?? '';
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\R{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * OpenAI 構造化のトークン制限を考慮して抽出テキストを切り詰める。
     */
    private function truncateTextForTokenLimit(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_EXTRACTED_TEXT_LENGTH) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_EXTRACTED_TEXT_LENGTH);
    }

    /**
     * 抽出テキストをレシピ JSON に構造化する。
     *
     * @param  list<string>  $unitNames
     * @param  list<string>  $categoryNames
     */
    private function structureRecipeFromText(string $text, array $unitNames, array $categoryNames, string $sourceDescription): ParsedRecipe
    {
        $decoded = $this->requestJsonCompletion(
            model: $this->textModel,
            messages: [
                [
                    'role' => 'system',
                    'content' => $this->structureSystemPrompt($unitNames, $categoryNames),
                ],
                [
                    'role' => 'user',
                    'content' => <<<USER
{$sourceDescription}このテキストだけを根拠に、指定の JSON 形式へ構造化してください。テキストにない情報は推測しないでください。

{$text}
USER,
                ],
            ],
            failureContext: 'OpenAI recipe structuring failed.',
        );

        $this->logRecipeParseDiagnostic('structure', [
            'source_description' => $sourceDescription,
            'text_model' => $this->textModel,
            'input_text' => $text,
            'structured_json' => $decoded,
        ]);

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
                __('operations.ai.recipe.parse_img'),
                $failureContext,
                [
                    'exception_message' => $e->getMessage(),
                ],
                __METHOD__,
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
                __('operations.ai.recipe.parse_img'),
                'OpenAI API returned empty content.',
                callerMethod: __METHOD__,
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
                __('operations.ai.recipe.parse_img'),
                'Failed to decode OpenAI JSON response.',
                [
                    'exception_message' => $e->getMessage(),
                ],
                __METHOD__,
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
     * @param  list<string>  $categoryNames
     */
    private function structureSystemPrompt(array $unitNames, array $categoryNames): string
    {
        $unitList = implode(', ', $unitNames);
        $categoryList = $categoryNames !== [] ? implode(', ', $categoryNames) : '（なし）';

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
      "categoryName": "材料カテゴリ（string。見出し行または既存カテゴリー名。判別不可なら空文字）"
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

categoryName の判定手順（厳守・最重要ルール）:
1. テキストを上から走査し、見出し行（例: 「☆ソース」「☆調味料」「A」「たれ」「仕上げ」）の出現位置を記録する
2. 各材料について、その材料がテキスト中で見出し行より「前」に出現するか「後」に出現するかで判定する
3. 見出し行より前 → categoryName は必ず空文字にする。例外なし。
4. 見出し行より後 → categoryName にその見出し名を使う（既存カテゴリーに一致すればその名前）
5. 見出し行がない場合 → 空文字
- 既存カテゴリー: {$categoryList}
- 禁止: 材料の意味・種類・用途で categoryName を推測すること。しょうゆ・みりん・砂糖等であっても、見出し行より前にあれば categoryName は空文字。
- 判定基準はテキスト内の行番号（出現順序）のみ。材料がどの食品カテゴリーに属するかは一切無関係。

categoryName の具体例:
入力テキスト:
  サラダ油 大さじ1/2
  しょうゆ 大さじ1と1/2
  ☆調味料
  酒 大さじ1
  みりん 大さじ2
正しい出力:
  サラダ油 → categoryName: ""（☆調味料より前）
  しょうゆ → categoryName: ""（☆調味料より前）
  酒 → categoryName: "☆調味料"（☆調味料より後）
  みりん → categoryName: "☆調味料"（☆調味料より後）
誤った出力（これをやってはいけない）:
  しょうゆ → categoryName: "☆調味料"（×意味で分類している。しょうゆは見出し行より前なので空文字が正解）

括弧付き併記:
- 「1枚(250g)」は括弧外を優先（quantityDisplay="1", unitName=枚）

prefix 単位（大さじ・小さじ）:
- 「大１」「大1」は「大さじ1」、「小１」「小1」は「小さじ1」の略記として解釈する
- quantityDisplay には数値部分のみ（"1/2", "1と1/2" など。単位名は含めない）
- 「1/2」と「1と1/2」は別表記。「と」を省略しない

手順セクション:
- 上から順に steps[].instruction へ

不明な項目は null または空文字。JSON オブジェクトのみを返してください。
PROMPT;
    }

    /**
     * AI レシピ解析の切り分け調査用ログ（APP_DEBUG が true のときのみ）。
     *
     * @param  'ocr'|'structure'  $phase
     * @param  array<string, mixed>  $context
     */
    private function logRecipeParseDiagnostic(string $phase, array $context = []): void
    {
        if (! config('app.debug')) {
            return;
        }

        $this->logMessage(
            'info',
            __('operations.ai.recipe.parse_img'),
            "AI recipe parse diagnostic ({$phase})",
            null,
            null,
            array_merge(['phase' => $phase], $context),
            __METHOD__,
        );
    }
}
