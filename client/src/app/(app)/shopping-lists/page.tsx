import ShoppingLists from '@/pages/shopping/ShoppingLists';
import { Suspense } from 'react';
import { apiClient } from '@/lib/apiClient';
import { IGetShoppingItemsResponse } from '@/types/api';
import Loading from '../Loading';

async function ShoppingListsWithData() {
    const items = await apiClient<IGetShoppingItemsResponse>('/shopping/items');
    return <ShoppingLists fetchItems={items.data} />;
}

const Page = async () => {
    return (
        <Suspense fallback={<Loading />}>
            <ShoppingListsWithData />
        </Suspense>
    );
};

export default Page;
