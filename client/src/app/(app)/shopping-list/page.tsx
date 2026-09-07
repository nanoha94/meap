import React, { Suspense } from 'react';
import ShoppingListPage from '@/pages/shopping/ShoppingListPage';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { IGetShoppingItemIndexResponse } from '@/types';

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
