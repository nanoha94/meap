import ShoppingLists from '@/pages/shopping/ShoppingLists';
import { Suspense } from 'react';
import Loading from '../loading';
import { fetchShoppingItems } from '@/lib/api';
import { IGetShoppingItemsResponse } from '@/types/api';

async function ShoppingListsWithData() {
    const { data: items }: IGetShoppingItemsResponse =
        await fetchShoppingItems();
    return <ShoppingLists fetchItems={items} />;
}

const Page = async () => {
    return (
        <Suspense fallback={<Loading />}>
            <ShoppingListsWithData />
        </Suspense>
    );
};

export default Page;
