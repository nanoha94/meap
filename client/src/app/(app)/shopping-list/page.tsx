import ShoppingListPage from '@/pages/shopping/ShoppingListPage';
import { Suspense } from 'react';
import {
    IGetShoppingCategoryIndexResponse,
    IGetShoppingItemIndexResponse,
} from '@/types/api';
import { Loading } from '@/components/common';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';

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
        <ShoppingListPage
            fetchItems={items?.data ?? []}
            fetchCategories={categories?.data ?? []}
            errorMessage={errorMessage}
        />
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
