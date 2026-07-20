<?php

namespace App\Helpers;

use Illuminate\Validation\ValidationException;

class Quantity
{
    private const PRECISION = 1000;
    private const NOTATION_FRACTION = 'fraction';
    private const NOTATION_DECIMAL = 'decimal';
    private const NOTATION_INTEGER = 'integer';
    private const MIXED_FRACTION_SEPARATOR_TO = 'と';
    private const MIXED_FRACTION_SEPARATOR_SPACE = ' ';

    /** @var list<array{numerator: int, denominator: int, value: float}> */
    private const KNOWN_FRACTIONS = [
        ['numerator' => 1, 'denominator' => 2, 'value' => 0.5],
        ['numerator' => 1, 'denominator' => 3, 'value' => 1 / 3],
        ['numerator' => 2, 'denominator' => 3, 'value' => 2 / 3],
        ['numerator' => 1, 'denominator' => 4, 'value' => 0.25],
        ['numerator' => 3, 'denominator' => 4, 'value' => 0.75],
        ['numerator' => 1, 'denominator' => 8, 'value' => 0.125],
        ['numerator' => 3, 'denominator' => 8, 'value' => 0.375],
        ['numerator' => 5, 'denominator' => 8, 'value' => 0.625],
        ['numerator' => 7, 'denominator' => 8, 'value' => 0.875],
    ];

    /**
     * 数量入力文字列を数値に変換する
     *
     * @return float|null 空欄・不正値・負数は null
     */
    public static function parseQuantityDisplayToNumber(string $text): ?float
    {
        $trimmed = self::sanitizeQuantityDisplayInput($text);

        if ($trimmed === '' || str_starts_with($trimmed, '-')) {
            return null;
        }

        // 帯分数（「と」区切り）の場合
        if (preg_match('/^(\d+)と(\d+)\/(\d+)$/', $trimmed, $matches) === 1) {
            $whole = (int) $matches[1]; // 整数部分
            $numerator = (int) $matches[2]; // 分子
            $denominator = (int) $matches[3]; // 分母

            // 分母が0の場合は null を返す
            if ($denominator === 0) {
                return null;
            }

            return self::roundQuantity($whole + $numerator / $denominator);
        }

        // 帯分数（スペース区切り）の場合
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $trimmed, $matches) === 1) {
            $whole = (int) $matches[1];
            $numerator = (int) $matches[2];
            $denominator = (int) $matches[3];

            // 分母が0の場合は null を返す
            if ($denominator === 0) {
                return null;
            }

            return self::roundQuantity($whole + $numerator / $denominator);
        }

        // 分数の場合
        if (preg_match('/^(\d+)\/(\d+)$/', $trimmed, $matches) === 1) {
            $numerator = (int) $matches[1];
            $denominator = (int) $matches[2];

            // 分母が0の場合は null を返す
            if ($denominator === 0) {
                return null;
            }

            return self::roundQuantity($numerator / $denominator);
        }

        // 小数の場合
        if (preg_match('/^\d*\.?\d+$/', $trimmed) === 1) {
            $parsed = (float) $trimmed;

            // 数値でない、または負数の場合は null を返す
            if (! is_finite($parsed) || $parsed < 0) {
                return null;
            }

            return self::roundQuantity($parsed);
        }

        // 整数の場合
        if (preg_match('/^\d+$/', $trimmed) === 1) {
            return (float) $trimmed;
        }

        return null;
    }

    /**
     * 既知分数にマッチすれば分数表記、それ以外は小数文字列で返す
     */
    public static function formatQuantityDisplay(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        return self::formatAsFraction($value) ?? self::formatAsDecimal($value);
    }

    /**
     * 数量の表示表記を正規化する
     */
    public static function normalizeQuantityDisplay(string $text, float $value): ?string
    {
        $trimmed = self::sanitizeQuantityDisplayInput($text);

        if ($trimmed === '' || $value === null) {
            return null;
        }

        // 数量の表記種別を判定する
        $notation = self::detectQuantityNotation($trimmed);

        // 分数の場合
        if ($notation === self::NOTATION_FRACTION) {
            $separator = self::detectMixedFractionSeparator($trimmed);

            return self::formatAsFraction($value, $separator) ?? self::formatAsDecimal($value);
        }

        // 小数の場合
        if ($notation === self::NOTATION_DECIMAL) {
            return self::formatAsDecimal($value);
        }

        // 整数の場合
        if ($notation === self::NOTATION_INTEGER) {
            return (string) (int) round($value);
        }

        return null;
    }

    /**
     * quantityDisplay に混入した単位名を除去する
     *
     * @param  string|null  $unitPosition  単位マスタの position（'prefix' は先頭、'suffix' は末尾）
     */
    public static function stripUnitFromDisplay(string $display, ?string $unitName, ?string $unitPosition = null): string
    {
        $trimmed = trim($display);

        if ($trimmed === '' || $unitName === null || $unitName === '') {
            return $trimmed;
        }

        $normalizedUnitName = trim($unitName);

        if ($unitPosition === 'prefix') {
            if (! str_starts_with($trimmed, $normalizedUnitName)) {
                return $trimmed;
            }

            return trim(substr($trimmed, strlen($normalizedUnitName)));
        }

        if ($unitPosition === 'suffix') {
            if (! str_ends_with($trimmed, $normalizedUnitName)) {
                return $trimmed;
            }

            return trim(substr($trimmed, 0, -strlen($normalizedUnitName)));
        }

        return $trimmed;
    }

    /**
     * quantity / quantityDisplay のペアを正規化する（AI 解析など、バリデーション例外を投げない用途）
     *
     * display を優先して parse し、display のみ欠落時は quantity から display を補完する。
     *
     * @return array{quantity: ?float, quantityDisplay: ?string}
     */
    public static function normalizeQuantityPair(
        ?float $quantity,
        ?string $quantityDisplay,
        bool $requiresQuantity,
    ): array {
        return self::resolveQuantityPair($quantity, $quantityDisplay, $requiresQuantity, validateDisplay: false);
    }

    /**
     * quantityDisplay 入力から quantity / quantityDisplay のペアを正規化する
     *
     * @return array{quantity: ?float, quantityDisplay: ?string}
     *
     * @throws ValidationException
     */
    public static function normalizeQuantityFromDisplay(
        ?string $quantityDisplay,
        bool $requiresQuantity,
        string $errorKey = 'quantityDisplay',
    ): array {
        return self::resolveQuantityPair(null, $quantityDisplay, $requiresQuantity, validateDisplay: true, errorKey: $errorKey);
    }

    /**
     * quantity / quantityDisplay のペアを正規化する
     *
     * @return array{quantity: ?float, quantityDisplay: ?string}
     *
     * @throws ValidationException validateDisplay=true かつ不正入力時
     */
    private static function resolveQuantityPair(
        ?float $quantity,
        ?string $quantityDisplay,
        bool $requiresQuantity,
        bool $validateDisplay,
        string $errorKey = 'quantityDisplay',
    ): array {
        // 数量が必須でない場合は null を返す
        if (! $requiresQuantity) {
            return [
                'quantity' => null,
                'quantityDisplay' => null,
            ];
        }

        $trimmed = $quantityDisplay !== null ? self::sanitizeQuantityDisplayInput($quantityDisplay) : '';
        $separatorHint = $trimmed !== '' ? self::detectMixedFractionSeparator($trimmed) : null;

        if ($trimmed !== '') {
            $parsed = self::parseQuantityDisplayToNumber($trimmed);

            if ($parsed !== null) {
                return [
                    'quantity' => $parsed,
                    'quantityDisplay' => self::normalizeQuantityDisplay($trimmed, $parsed),
                ];
            }

            if ($validateDisplay) {
                throw ValidationException::withMessages([
                    $errorKey => [__('validation.invalid_quantity_display')],
                ]);
            }

            return self::pairFromQuantity($quantity, $separatorHint);
        }

        if ($validateDisplay) {
            throw ValidationException::withMessages([
                $errorKey => [__('validation.required_when_unit_requires_quantity', [
                    'attribute' => 'ingredients.*.quantityDisplay',
                ])],
            ]);
        }

        return self::pairFromQuantity($quantity);
    }

    /**
     * @return array{quantity: ?float, quantityDisplay: ?string}
     */
    private static function pairFromQuantity(?float $quantity, ?string $mixedFractionSeparator = null): array
    {
        if ($quantity === null) {
            return [
                'quantity' => null,
                'quantityDisplay' => null,
            ];
        }

        $separator = $mixedFractionSeparator ?? self::MIXED_FRACTION_SEPARATOR_TO;
        $display = self::formatAsFraction($quantity, $separator) ?? self::formatAsDecimal($quantity);

        return [
            'quantity' => $quantity,
            'quantityDisplay' => $display !== '' ? $display : null,
        ];
    }

    /**
     * 数量入力を NFKC 正規化し、全角スペースを半角に置換する
     */
    private static function sanitizeQuantityDisplayInput(string $text): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return '';
        }
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($trimmed, \Normalizer::FORM_KC);
            if (is_string($normalized)) {
                $trimmed = $normalized;
            }
        }

        // NFKC 後も残る場合の保険（U+3000 → 半角スペース）
        return str_replace("\u{3000}", ' ', $trimmed);
    }

    /**
     * 数量を小数第3位まで丸める
     */
    private static function roundQuantity(float $value): float
    {
        return round($value * self::PRECISION) / self::PRECISION;
    }

    /**
     * 数量がほぼ等しいかどうかを判定する（差が1/1000以下の場合は等しいとみなす）
     */
    private static function isNearlyEqual(float $a, float $b): bool
    {
        return abs($a - $b) < 1 / self::PRECISION;
    }

    /**
     * 既知分数にマッチすれば分数表記、それ以外は小数文字列で返す
     * @return array{numerator: int, denominator: int}|null
     */
    private static function findKnownFraction(float $fractionalPart): ?array
    {
        $rounded = self::roundQuantity($fractionalPart);

        foreach (self::KNOWN_FRACTIONS as $fraction) {
            if (self::isNearlyEqual($rounded, $fraction['value'])) {
                return [
                    'numerator' => $fraction['numerator'], // 分子
                    'denominator' => $fraction['denominator'], // 分母
                ];
            }
        }

        return null;
    }

    /**
     * 小数表記にフォーマットする
     */
    private static function formatAsDecimal(float $value): string
    {
        $rounded = self::roundQuantity($value);

        // 整数の場合は整数表記にフォーマットする
        if (self::isNearlyEqual($rounded, round($rounded))) {
            return (string) (int) round($rounded);
        }

        // 小数第10位までの文字列を取得し、末尾の0を削除する
        return rtrim(rtrim(sprintf('%.10F', $rounded), '0'), '.');
    }

    /**
     * 帯分数の区切り文字を入力表記から判定する（判定不能時は「と」）
     */
    private static function detectMixedFractionSeparator(string $text): string
    {
        $trimmed = self::sanitizeQuantityDisplayInput($text);

        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $trimmed) === 1) {
            return self::MIXED_FRACTION_SEPARATOR_SPACE;
        }

        if (preg_match('/^(\d+)と(\d+)\/(\d+)$/', $trimmed) === 1) {
            return self::MIXED_FRACTION_SEPARATOR_TO;
        }

        return self::MIXED_FRACTION_SEPARATOR_TO;
    }

    /**
     * 分数表記にフォーマットする
     *
     * @param  string  $separator  帯分数の区切り（デフォルト「と」）
     */
    private static function formatAsFraction(float $value, string $separator = self::MIXED_FRACTION_SEPARATOR_TO): ?string
    {
        $rounded = self::roundQuantity($value);

        if ($rounded < 0) {
            return null;
        }

        // 整数部分を取得
        $whole = (int) floor($rounded);
        // 小数部分を取得
        $fractional = self::roundQuantity($rounded - $whole);

        // 小数部分が0の場合は整数部分を返す
        if ($fractional === 0.0) {
            return (string) $whole;
        }

        // 10 以上は帯分数化せず、小数表記へフォールバックする
        if ($rounded >= 10) {
            return null;
        }

        $known = self::findKnownFraction($fractional);
        if ($known === null) {
            return null;
        }

        // 整数部分が0の場合は分数表記にフォーマットする
        if ($whole === 0) {
            return "{$known['numerator']}/{$known['denominator']}";
        }

        // 整数部分が0でない場合は帯分数表記にフォーマットする
        return "{$whole}{$separator}{$known['numerator']}/{$known['denominator']}";
    }

    /**
     * 数量の表記種別を判定する
     */
    private static function detectQuantityNotation(string $text): ?string
    {
        $trimmed = self::sanitizeQuantityDisplayInput($text);

        if ($trimmed === '') {
            return null;
        }

        // スラッシュが含まれる場合は分数表記
        if (str_contains($trimmed, '/')) {
            return self::NOTATION_FRACTION;
        }

        // ピリオドが含まれる場合は小数表記
        if (str_contains($trimmed, '.')) {
            return self::NOTATION_DECIMAL;
        }

        // 数字のみの場合は整数表記
        if (preg_match('/^\d+$/', $trimmed) === 1) {
            return self::NOTATION_INTEGER;
        }

        return null;
    }
}
