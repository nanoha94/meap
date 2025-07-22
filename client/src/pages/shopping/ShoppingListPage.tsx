'use client';
import { TextButton } from '@/components/common';
import LoadingAnimation from '@/components/common/LoadingAnimation';
import {
    AddShoppingItemButton,
    ShoppingCategorySettingDialog,
    ShoppingItemSettingDialog,
    ShoppingList,
} from '@/models/shopping/components';
import { useShoppingStore } from '@/models/shopping/hooks';
import { IGetShoppingItemsResponse } from '@/types/api';
import { ChevronRight } from 'lucide-react';
import React from 'react';

interface Props {
    fetchItems?: IGetShoppingItemsResponse['data'];
}

const ShoppingListPage: React.FC<Props> = ({ fetchItems }) => {
    const {
        items: storeItems,
        setServerItems,
        setItems: setStoreItems,
        openDialog,
        isLoadingCategories,
        isLoadingItems,
    } = useShoppingStore();
    const [isLoading, setIsLoading] = React.useState(false);

    React.useEffect(() => {
        if (fetchItems) {
            setStoreItems(fetchItems);
            setServerItems(fetchItems);
        }
    }, [fetchItems]);

    React.useEffect(() => {
        setIsLoading(isLoadingCategories || isLoadingItems);
    }, [isLoadingCategories, isLoadingItems]);

    return (
        <>
            {isLoading && <LoadingAnimation />}
            {/* メインコンテンツ */}
            <main className="p-5">
                <div className="pb-12 flex flex-col gap-y-7">
                    {/* 買い物リスト */}
                    <ShoppingList items={storeItems} />
                    <TextButton
                        colorVariant="secondary"
                        onClick={() => {
                            openDialog('categorySetting', undefined);
                        }}>
                        カテゴリーの追加・編集
                        <ChevronRight size={20} />
                    </TextButton>
                </div>
                <AddShoppingItemButton />
            </main>
            {/* アイテム追加・編集ダイアログ */}
            <ShoppingItemSettingDialog />
            {/* カテゴリー設定ダイアログ */}
            <ShoppingCategorySettingDialog />
        </>
    );
};

export default ShoppingListPage;
