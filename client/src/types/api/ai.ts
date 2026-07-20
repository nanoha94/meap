import { IBaseApiResponseWithData } from './common';
import { IImageWithFile } from './image';

//--------------------------------
// レスポンス型
//--------------------------------
export type IAiRecipeParseResponse = IBaseApiResponseWithData<IParsedRecipe>;
export type IAiUsageStatusResponse = IBaseApiResponseWithData<IAiUsageStatus>;

//--------------------------------
// リクエスト型
//--------------------------------
// 画像からレシピ情報を AI 解析
export interface IPostAiRecipeParseImageRequest {
    image: IImageWithFile;
}

// URL からレシピ情報を AI 解析
export interface IPostAiRecipeParseUrlRequest {
    url: string;
}

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
