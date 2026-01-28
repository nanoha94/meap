import { IIngredientItem } from "@/types";

/**
 * 材料をフォーマット
 * @param item 材料のデータ
 * @returns フォーマットされた材料
 */
export const formatIngredient = (item: IIngredientItem) => {
    if (!item) {
        return '';
    }

    let result: string = item.name;
    if (result && item.unit) {
        result += ` / ${item.quantity || ''}${item.unit.name}`;
    }
    return result;
};