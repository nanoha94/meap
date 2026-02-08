import { IBaseApiIndexResponse, IBaseApiResponse, IBaseApiResponseWithData } from "./common";
import { IRecipeListItem } from "./recipe";

//--------------------------------
// レスポンス型
//--------------------------------

// 献立プラン一覧取得
export type IGetMealPlanIndexResponse = IBaseApiIndexResponse<IMealPlan[]>;

// 献立プラン詳細取得
export type IGetMealPlanShowResponse = IBaseApiResponseWithData<IMealPlan>;

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
    date?: string;
    meals: {id?: string; categoryId: string; recipeIds: string[]}[];
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

export interface IMeal {
    id: string;
    category: IMealCategory;
    recipes: IRecipeListItem[];
}

export interface IMealPlan {
    id: string;
    date: string;
    meals: IMeal[];
}
