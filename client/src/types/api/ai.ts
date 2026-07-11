import { IBaseApiResponseWithData } from './common';

//--------------------------------
// レスポンス型
//--------------------------------
export type IAiRecipeParseResponse = IBaseApiResponseWithData<IParsedRecipe>;
export type IAiUsageStatusResponse = IBaseApiResponseWithData<IAiUsageStatus>;

//--------------------------------
// データ型
//--------------------------------
export interface IParsedRecipeIngredient {
    name: string;
    quantity: number | null;
    quantityDisplay: string | null;
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

export interface IAiUsageStatus {
    plan: string;
    monthlyRemaining: number;
    monthlyLimit: number;
    packRemaining: number;
    resetsAt: string | null;
}
