import { IIngredientItem } from '@/types';
import { formatQuantityDisplay } from '@/utils';

/**
 * 材料の数量・単位部分をフォーマット
 * @param item 材料のデータ
 * @returns フォーマットされた数量・単位（例: "1/2個", "大さじ1/2"）
 */
export const formatIngredientQuantity = (item: IIngredientItem): string => {
    if (!item) {
        return '';
    }

    const quantityStr = formatQuantityDisplay(
        item.quantity,
        item.quantityDisplay,
    );

    if (!item.unit) {
        return quantityStr;
    }

    if (item.unit.position === 'prefix') {
        return quantityStr
            ? `${item.unit.name}${quantityStr}`
            : item.unit.name;
    }

    return quantityStr
        ? `${quantityStr}${item.unit.name}`
        : item.unit.name;
};

/**
 * 材料をフォーマット
 * @param item 材料のデータ
 * @returns フォーマットされた材料（例: "玉ねぎ 1/2個", "塩 大さじ1/2"）
 */
export const formatIngredient = (item: IIngredientItem): string => {
    if (!item) {
        return '';
    }

    const quantityPart = formatIngredientQuantity(item);

    if (!quantityPart) {
        return item.name;
    }

    return `${item.name} ${quantityPart}`;
};