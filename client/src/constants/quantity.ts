/** 数量の精度 */
export const QUANTITY_PRECISION = 1000;

/** 帯分数の区切り */
export const MIXED_FRACTION_SEPARATOR_TO = 'と';
export const MIXED_FRACTION_SEPARATOR_SPACE = ' ';

/** 数量入力のバリデーションエラーメッセージ */
export const QUANTITY_INVALID_MESSAGE =
    '数量は整数・小数・分数で入力してください（例: 2、1.5、1/2、1と1/2）';

/** 数量の表記種別 */
export const QUANTITY_NOTATION = {
    FRACTION: 'fraction',
    DECIMAL: 'decimal',
    INTEGER: 'integer',
} as const;
export type QuantityNotation =
    (typeof QUANTITY_NOTATION)[keyof typeof QUANTITY_NOTATION];

/** 既知の分数 */
export const KNOWN_FRACTIONS: ReadonlyArray<{
    numerator: number;   // 分子
    denominator: number; // 分母
    value: number;       // 分数の値
}> = [
        { numerator: 1, denominator: 2, value: 1 / 2 },
        { numerator: 1, denominator: 3, value: 1 / 3 },
        { numerator: 2, denominator: 3, value: 2 / 3 },
        { numerator: 1, denominator: 4, value: 1 / 4 },
        { numerator: 3, denominator: 4, value: 3 / 4 },
        { numerator: 1, denominator: 8, value: 1 / 8 },
        { numerator: 3, denominator: 8, value: 3 / 8 },
        { numerator: 5, denominator: 8, value: 5 / 8 },
        { numerator: 7, denominator: 8, value: 7 / 8 },
    ];
