import {
    KNOWN_FRACTIONS,
    MIXED_FRACTION_SEPARATOR_SPACE,
    MIXED_FRACTION_SEPARATOR_TO,
    QUANTITY_NOTATION,
    QUANTITY_PRECISION,
    QuantityNotation,
} from '@/constants';

/** 数量入力を NFKC 正規化し、全角スペースを半角に置換する */
const sanitizeQuantityDisplayInput = (text: string): string =>
    text
        .trim()
        .normalize('NFKC')
        .replace(/\u3000/g, ' ');

/** 数量を小数第3位まで丸める */
const roundQuantity = (value: number): number =>
    Math.round(value * QUANTITY_PRECISION) / QUANTITY_PRECISION;

/** 数量がほぼ等しいかどうかを判定する（差が1/1000以下の場合は等しいとみなす） */
const isNearlyEqual = (a: number, b: number): boolean =>
    Math.abs(a - b) < 1 / QUANTITY_PRECISION;

/** 既知の分数を探す */
const findKnownFraction = (
    fractionalPart: number,
): { numerator: number; denominator: number } | null => {
    const rounded = roundQuantity(fractionalPart);

    for (const fraction of KNOWN_FRACTIONS) {
        if (isNearlyEqual(rounded, fraction.value)) {
            return {
                numerator: fraction.numerator,      // 分子
                denominator: fraction.denominator,  // 分母
            };
        }
    }

    return null;
};

/** 小数表記にフォーマットする */
const formatAsDecimal = (value: number): string => {
    const rounded = roundQuantity(value);

    if (Number.isInteger(rounded)) {
        return String(rounded);
    }

    // 小数第10位までの文字列を取得し、末尾の0を削除する
    return rounded
        .toFixed(10)
        .replace(/\.?0+$/, '');
};

/** 帯分数の区切り文字を入力表記から判定する（判定不能時は「と」） */
const detectMixedFractionSeparator = (text: string): string => {
    const trimmed = sanitizeQuantityDisplayInput(text);

    if (/^(\d+)\s+(\d+)\/(\d+)$/.test(trimmed)) {
        return MIXED_FRACTION_SEPARATOR_SPACE;
    }

    if (/^(\d+)と(\d+)\/(\d+)$/.test(trimmed)) {
        return MIXED_FRACTION_SEPARATOR_TO;
    }

    return MIXED_FRACTION_SEPARATOR_TO;
};

/** 分数表記にフォーマットする
 * 
 * @param value 数量
 * @param separator 帯分数の区切り（デフォルト「と」）
 * @returns 分数表記。負数・10以上は null
 */
const formatAsFraction = (
    value: number,
    separator: string = MIXED_FRACTION_SEPARATOR_TO,
): string | null => {
    const rounded = roundQuantity(value);

    if (rounded < 0) {
        return null;
    }

    // 整数部分を取得
    const whole = Math.floor(rounded);
    // 小数部分を取得
    const fractional = roundQuantity(rounded - whole);

    // 小数部分が0の場合は整数部分を返す
    if (fractional === 0) {
        return String(whole);
    }

    // 10 以上は帯分数化せず、小数表記へフォールバックする
    if (rounded >= 10) {
        return null;
    }

    const known = findKnownFraction(fractional);
    if (!known) {
        return null;
    }

    if (whole === 0) {
        return `${known.numerator}/${known.denominator}`;
    }

    return `${whole}${separator}${known.numerator}/${known.denominator}`;
};

/**
 * 入力文字列の表記種別を判定する
 * @param text 入力文字列
 * @returns 'fraction' | 'decimal' | 'integer'。空欄・判定不能は null
 */
const detectQuantityNotation = (
    text: string,
): QuantityNotation | null => {
    const trimmed = sanitizeQuantityDisplayInput(text);

    if (trimmed === '') {
        return null;
    }

    // スラッシュが含まれる場合は分数
    if (trimmed.includes('/')) {
        return QUANTITY_NOTATION.FRACTION;
    }

    // ピリオドが含まれる場合は小数
    if (trimmed.includes('.')) {
        return QUANTITY_NOTATION.DECIMAL;
    }

    // 数字のみの場合は整数
    if (/^\d+$/.test(trimmed)) {
        return QUANTITY_NOTATION.INTEGER;
    }

    return null;
};

export type QuantityPair = {
    quantity: number | null;
    quantityDisplay: string | null;
};

/** quantity のみから quantity / quantityDisplay ペアを組み立てる */
const pairFromQuantity = (
    quantity: number | null,
    mixedFractionSeparator: string | null = null,
): QuantityPair => {
    if (quantity === null) {
        return { quantity: null, quantityDisplay: null };
    }

    const separator = mixedFractionSeparator ?? MIXED_FRACTION_SEPARATOR_TO;
    const display = formatAsFraction(quantity, separator) ?? formatAsDecimal(quantity);

    return {
        quantity,
        quantityDisplay: display !== '' ? display : null,
    };
};

/**
 * 数量入力文字列を数値に変換する
 * @param text 入力文字列（例: "1/2", "1 1/2", "1と1/2", "1.5", "2"）
 * @returns 変換結果。空欄・不正値・負数は null
 */
export const parseQuantityDisplayToNumber = (text: string): number | null => {
    const trimmed = sanitizeQuantityDisplayInput(text);

    if (trimmed === '' || trimmed.startsWith('-')) {
        return null;
    }

    // 帯分数（「と」区切り）の場合
    const mixedFractionToMatch = trimmed.match(/^(\d+)と(\d+)\/(\d+)$/);
    if (mixedFractionToMatch) {
        const whole = Number(mixedFractionToMatch[1]); // 整数部分
        const numerator = Number(mixedFractionToMatch[2]); // 分子
        const denominator = Number(mixedFractionToMatch[3]); // 分母

        // 分母が0の場合は null を返す
        if (denominator === 0) {
            return null;
        }

        return roundQuantity(whole + numerator / denominator);
    }

    // 帯分数（スペース区切り）の場合
    const mixedFractionMatch = trimmed.match(/^(\d+)\s+(\d+)\/(\d+)$/);
    if (mixedFractionMatch) {
        const whole = Number(mixedFractionMatch[1]);    // 整数部分
        const numerator = Number(mixedFractionMatch[2]); // 分子
        const denominator = Number(mixedFractionMatch[3]); // 分母

        // 分母が0の場合は null を返す
        if (denominator === 0) {
            return null;
        }

        return roundQuantity(whole + numerator / denominator);
    }

    // 分数の場合
    const fractionMatch = trimmed.match(/^(\d+)\/(\d+)$/);
    if (fractionMatch) {
        const numerator = Number(fractionMatch[1]); // 分子
        const denominator = Number(fractionMatch[2]); // 分母

        // 分母が0の場合は null を返す  
        if (denominator === 0) {
            return null;
        }

        return roundQuantity(numerator / denominator);
    }

    // 小数の場合
    if (/^\d*\.?\d+$/.test(trimmed)) {
        // 小数点を含む場合は小数としてパース
        const parsed = Number(trimmed);

        // 数値でない、または負数の場合は null を返す
        if (Number.isNaN(parsed) || parsed < 0) {
            return null;
        }

        return roundQuantity(parsed);
    }

    // 整数の場合
    if (/^\d+$/.test(trimmed)) {
        return Number(trimmed);
    }

    return null;
};

/**
 * 分数・小数入力の途中状態かどうかを判定する
 * 例: "1", "1/", "1 1" は途中、"abc" は false
 * @param text 入力文字列
 */
export const isPartialQuantityInput = (text: string): boolean => {
    const trimmed = sanitizeQuantityDisplayInput(text);

    if (trimmed === '') {
        return false;
    }

    // パースできる場合は途中状態でない
    if (parseQuantityDisplayToNumber(trimmed) !== null) {
        return false;
    }

    // 数字・スペース・スラッシュ・ピリオド・「と」のみの場合は途中状態
    return /^[\d\s/.と]+$/.test(trimmed);
};

/**
 * blur / 保存時に数量の表示表記を正規化する
 * @param text ユーザー入力文字列
 * @param value パース済みの数量
 * @returns 正規化された表示表記。空欄または value が null のときは null
 */
export const normalizeQuantityDisplay = (
    text: string,
    value: number | null,
): string | null => {
    const trimmed = sanitizeQuantityDisplayInput(text);

    if (trimmed === '' || value === null) {
        return null;
    }

    const notation = detectQuantityNotation(trimmed);

    // 分数の場合
    if (notation === QUANTITY_NOTATION.FRACTION) {
        const separator = detectMixedFractionSeparator(trimmed);

        return formatAsFraction(value, separator) ?? formatAsDecimal(value);
    }

    // 小数の場合
    if (notation === QUANTITY_NOTATION.DECIMAL) {
        return formatAsDecimal(value);
    }

    // 整数の場合
    if (notation === QUANTITY_NOTATION.INTEGER) {
        return String(Math.round(value));
    }

    return null;
};


/**
 * quantityDisplay 入力から quantity / quantityDisplay のペアを正規化する
 * display が空・不正のときは quantity から補完する（クライアント用・非 strict）
 */
export const normalizeQuantityFromDisplay = (
    quantityDisplay: string | null | undefined,
    requiresQuantity: boolean,
    quantity: number | null = null,
): QuantityPair => {
    if (!requiresQuantity) {
        return { quantity: null, quantityDisplay: null };
    }

    const trimmed = quantityDisplay != null
        ? sanitizeQuantityDisplayInput(quantityDisplay)
        : '';
    const separatorHint = trimmed !== ''
        ? detectMixedFractionSeparator(trimmed)
        : null;

    if (trimmed !== '') {
        const parsed = parseQuantityDisplayToNumber(trimmed);

        if (parsed !== null) {
            return {
                quantity: parsed,
                quantityDisplay: normalizeQuantityDisplay(trimmed, parsed),
            };
        }

        return pairFromQuantity(quantity, separatorHint);
    }

    return pairFromQuantity(quantity);
};

/**
 * 数量の表示用文字列を返す
 * 保存済みの表示表記があればそれを優先し、なければ数値から生成する
 * @param value 数量
 * @param display 保存済みの表示表記
 * @returns 表示用文字列。value と display がともに空のときは空文字
 */
export const formatQuantityDisplay = (
    value: number | null,
    display?: string | null,
): string => {
    // 保存済みの表示表記があればそれを優先する
    if (display != null && display !== '') {
        return display;
    }

    // value が null の場合は空文字を返す
    if (value === null) {
        return '';
    }

    // 既知分数にマッチすれば分数表記、それ以外は小数文字列で返す
    return formatAsFraction(value) ?? formatAsDecimal(value);
};

/**
 * 数量配列を合算する（float 誤差を 1/1000 単位で丸める）
 * @param values 合算対象の数量
 * @returns 合算結果
 */
export const sumQuantities = (values: number[]): number => {
    const sum = values.reduce((acc, value) => acc + value, 0);
    return roundQuantity(sum);
};

/**
 * 合算後の quantityDisplay を生成する
 * - いずれかが小数表記 → 小数表記
 * - それ以外 → 分数正規化（整数なら "2" 等）
 * - 帯分数は元の表記に関わらず常に「1と1/2」形式（`MIXED_FRACTION_SEPARATOR_TO`）
 */
export const formatSummedQuantityDisplay = (
    sum: number,
    sources: ReadonlyArray<{ quantityDisplay: string | null }>,
): string | null => {
    const hasDecimal = sources.some((source) => {
        const trimmed = source.quantityDisplay?.trim() ?? '';

        return (
            trimmed !== '' &&
            detectQuantityNotation(trimmed) === QUANTITY_NOTATION.DECIMAL
        );
    });

    if (hasDecimal) {
        return formatAsDecimal(sum);
    }

    // 買い物リスト加算では帯分数を常に「と」形式で出力する
    return formatAsFraction(sum, MIXED_FRACTION_SEPARATOR_TO) ?? formatAsDecimal(sum);
};
