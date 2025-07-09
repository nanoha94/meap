import ShoppingLists from '@/pages/shopping/ShoppingLists';
import { Suspense } from 'react';
import { IGetShoppingItemsResponse } from '@/types/api';
import Loading from '../Loading';
import { apiClient } from '@/lib/apiClient';
import { SnackbarHandler } from '@/components/handlers';
import { timeout_ms } from '@/constants';

async function ShoppingListsWithData() {
    let items: IGetShoppingItemsResponse;
    let errorMessage: string = '';
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout_ms);

        items = await apiClient('/shopping/items', {
            signal: controller.signal,
        });
        clearTimeout(timeoutId);
    } catch (error) {
        items = { data: [] };
        console.error(error);
        // エラーオブジェクトから安全に文字列を抽出
        errorMessage =
            error instanceof Error
                ? error.message
                : typeof error === 'string'
                  ? error
                  : 'データの取得に失敗しました';
    }

    return (
        <>
            {errorMessage && (
                <SnackbarHandler type="error" message={errorMessage} />
            )}
            <ShoppingLists fetchItems={items?.data} />
        </>
    );
}

const Page = () => {
    return (
        <Suspense fallback={<Loading />}>
            <ShoppingListsWithData />
        </Suspense>
    );
};

export default Page;
