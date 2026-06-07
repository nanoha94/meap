import { IBaseApiResponseWithData } from './common';

//--------------------------------
// レスポンス型
//--------------------------------
export type IAiRecipeParseResponse = IBaseApiResponseWithData<IParsedRecipe>;

//--------------------------------
// データ型
//--------------------------------
export interface IParsedRecipeIngredient {
    name: string;
    quantity: number | null;
    unitName: string;
    categoryName: string;
}

export interface IParsedRecipeStep {
    instruction: string;
}

export interface IParsedRecipe {
    name: string;
    servingCount: number | null;
    ingredients: IParsedRecipeIngredient[];
    steps: IParsedRecipeStep[];
}
