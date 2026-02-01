import { IBaseApiIndexResponse, IBaseApiResponse } from "./common";
import { IRecipe, IRecipeListItem } from "./recipe";

//--------------------------------
// レスポンス型
//--------------------------------

// 献立プラン一覧取得
export type IGetMealPlanIndexResponse = IBaseApiIndexResponse<IRecipe[]>;

// 献立プラン作成
export type IPostMealPlanResponse = IBaseApiResponse;

// 献立プラン更新
export type IPutMealPlanResponse = IBaseApiResponse;

// 献立プラン削除
export type IDeleteMealPlanResponse = IBaseApiResponse;

//--------------------------------
// リクエスト型
//--------------------------------
// 献立プラン作成/更新
export interface IPostPutMealPlanRequest {
    id?: string;
    date: string;
    mealCategoryId: string;
    recipeIds: string[];
}

//--------------------------------
// データ型
//--------------------------------
export interface IMealCategory {
    id: string;
    name: string;
    colorCodeHex: string;
    order: number;
}

export interface IMealPlan {
    id: string;
    date: string;
    category: IMealCategory;
    recipes: IRecipeListItem[];
}

export interface IMealPlans {
    date: string;
    mealPlans: IMealPlan[];
}