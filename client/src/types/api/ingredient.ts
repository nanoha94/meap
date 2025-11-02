export type IGetIngredientCategoryResponse = {
    data: IIngredientCategory[];
    total: number;
};

export interface IIngredientCategory {
    id: string;
    name: string;
    order: number;
}

export interface IIngredientUnit {
    id: string;
    name: string;
    position: 'prefix' | 'suffix';
    requiresQuantity: boolean;
    order: number;
}

export interface IIngredient {
    id?: string; // 新規作成時はidなしも許容
    name: string;
    quantity: number | null;
    unitId: string;
    categoryId: string;
    order?: number;
}
