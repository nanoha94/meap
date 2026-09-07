import { IBaseApiIndexResponse } from './common';

//--------------------------------
// レスポンス型
//--------------------------------
// 食材単位一覧取得
export type IGetIngredientUnitIndexResponse = IBaseApiIndexResponse<IIngredientUnit[]>;

//--------------------------------
// データ型
//--------------------------------
export interface IIngredientCategory {
    id: string;
    name: string;
    isDefault?: boolean;
    order: number;
}

export interface IIngredientItem {
    id: string; // 新規作成時はidなしも許容
    name: string;
    quantity: number | null;
    quantityDisplay: string | null;
    unit: IIngredientUnit | null;
    categoryId: string;
    order?: number;
}

export interface IIngredientUnit {
    id: string;
    name: string;
    position?: 'prefix' | 'suffix';
    requiresQuantity: boolean;
    order: number;
}
