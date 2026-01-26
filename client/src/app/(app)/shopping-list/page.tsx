import ShoppingListPage from '@/pages/shopping/ShoppingListPage';
import { Suspense } from 'react';
import { IGetShoppingItemIndexResponse, } from '@/types/api';
import { Loading } from '@/components/common';
import { fetchData } from '@/lib/apiClient';

async function ShoppingListsWithData() {
    const { data: items, errorMessage } = await fetchData<IGetShoppingItemIndexResponse>('/shopping-items');

    return (
        <ShoppingListPage
            fetchItems={items?.data ?? []}
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
