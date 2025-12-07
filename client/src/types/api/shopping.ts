import { IBaseApiIndexResponse } from './common';

//--------------------------------
// レスポンス型
//--------------------------------
// 買い物アイテム一覧取得
export type IGetShoppingItemIndexResponse = IBaseApiIndexResponse<
    IShoppingItem[]
>;

// 買い物カテゴリー一覧取得
export type IGetShoppingCategoryIndexResponse = IBaseApiIndexResponse<
    IShoppingCategory[]
>;

//--------------------------------
// リクエストデータ型
//--------------------------------
// 買い物アイテム更新
export interface IPutShoppingItemRequestData {
    id: string;
    name: string;
    isPinned: boolean;
    isChecked: boolean;
    categoryId: string;
    tags: { id?: string; name: string }[];
    order: number;
}

// 買い物アイテム作成
export interface IPostShoppingItemRequestData {
    name: string;
    categoryId: string;
    tags: { id?: string; name: string }[];
}

// 買い物カテゴリー作成
export interface IPostShoppingCategoryRequestData {
    name: string;
    order: number;
}

// 買い物カテゴリー更新
export interface IPutShoppingCategoryRequestData {
    id: string;
    name: string;
    order: number;
}

//--------------------------------
// データ型
//--------------------------------
// 買い物アイテム
export interface IShoppingItem {
    id: string;
    name: string;
    isPinned: boolean;
    isChecked: boolean;
    categoryId: string;
    tags: { id: string; name: string }[];
    order: number;
}

// 買い物カテゴリー
export interface IShoppingCategory {
    id: string;
    name: string;
    isDefault: boolean;
    order: number;
}

// 買い物タグ
export interface IShoppingTag {
    id: string;
    name: string;
}
