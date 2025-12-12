import ShoppingListPage from '@/pages/shopping/ShoppingListPage';
import { Suspense } from 'react';
import {
    IGetShoppingCategoryIndexResponse,
    IGetShoppingItemIndexResponse,
} from '@/types/api';
import { Header, Loading } from '@/components/common';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';
import { SnackbarHandler } from '@/components/handlers';
import HeaderButton from '@/models/shopping/components/HeaderButton/HeaderButton';

async function ShoppingListsWithData() {
    const { data, errorMessage } = await fetchDataParallel<
        [IGetShoppingItemIndexResponse, IGetShoppingCategoryIndexResponse]
    >([
        signal =>
            apiClient<IGetShoppingItemIndexResponse>('/shopping-items', {
                signal,
            }),
        signal =>
            apiClient<IGetShoppingCategoryIndexResponse>(
                '/shopping-categories',
                { signal },
            ),
    ]);

    const [items, categories] = data ?? [null, null];

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
