import ShoppingListPage from '@/pages/shopping/ShoppingListPage';
import { Suspense } from 'react';
import {
    IGetShoppingCategoryIndexResponse,
    IGetShoppingItemIndexResponse,
} from '@/types/api';
import Loading from '../loading';
import { apiClient } from '@/lib/apiClient';
import { SnackbarHandler } from '@/components/handlers';
import { TIMEOUT_MS } from '@/constants';
import { Header } from '@/components/common';
import HeaderButton from '@/models/shopping/components/HeaderButton/HeaderButton';

async function ShoppingListsWithData() {
    let items: IGetShoppingItemIndexResponse | null = null;
    let categories: IGetShoppingCategoryIndexResponse | null = null;
    let errorMessage: string = '';
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        // 2つのリクエストを並列実行
        [items, categories] = await Promise.all([
            apiClient<IGetShoppingItemIndexResponse>('/shopping-items', {
                signal: controller.signal,
            }),
            apiClient<IGetShoppingCategoryIndexResponse>(
                '/shopping-categories',
                {
                    signal: controller.signal,
                },
            ),
        ]);

        clearTimeout(timeoutId);
    } catch (error) {
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
            <Header title="買い物リスト">
                <HeaderButton />
            </Header>
            <main>
                <ShoppingListPage
                    fetchItems={items?.data ?? []}
                    fetchCategories={categories?.data ?? []}
                />
            </main>
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
