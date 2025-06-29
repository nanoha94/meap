import { IGetShoppingItemsResponse } from '@/types/api';
import { apiClient } from './apiClient';

// サーバーコンポーネントでのAPIリクエスト
// Next.jsではfetchを使用するのが推奨

// async function getShoppingCategories() {
//     try {
//         const res = await fetch('/shopping/categories');

//         if (!res.ok) {
//             throw new Error('Failed to fetch shopping categories');
//         }

//         return res.json();
//     } catch (error) {
//         console.error(error);
//         return [];
//     }
// }

export async function getShoppingItems(): Promise<IGetShoppingItemsResponse> {
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
