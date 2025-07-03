import { IGetShoppingItemsResponse, IGetUserResponse } from '@/types/api';
import { apiClient } from './apiClient';

// サーバーコンポーネントでのAPIリクエスト
// Next.jsではfetchを使用するのが推奨

// ユーザー取得
export async function fetchUser(): Promise<IGetUserResponse | null> {
    try {
        return await apiClient('/user');
    } catch (error) {
        console.error(error);
        return null;
    }
}

// 買い物アイテム取得
export async function fetchShoppingItems(): Promise<IGetShoppingItemsResponse> {
    try {
        return await apiClient('/shopping/items');
    } catch (error) {
        console.error(error);
        return { data: [] }; // エラー時は空のデータを返す
    }
}

// 使用例：POSTリクエスト
// export async function createShoppingItem(newItem: { name: string; }) {
//     try {
//         return await apiClient('/shopping/items', {
//             method: 'POST',
//             body: newItem,
//         });
//     } catch (error) {
//         console.error(error);
//         return null;
//     }
// }
