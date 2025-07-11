'use client';
import { Header, TextButton } from '@/components/common';
import LoadingAnimation from '@/components/common/LoadingAnimation';
import {
    ShoppingCategorySettingDialog,
    ShoppingItemSettingDialog,
    ShoppingList,
} from '@/models/shopping/components';
import { SHOPPING_ITEM_EDIT_MODE } from '@/models/shopping/constants/dialogs';
import { useShoppingStore } from '@/models/shopping/hooks';
import {
    IGetShoppingCategoriesResponse,
    IGetShoppingItemsResponse,
} from '@/types/api';
import { CalendarDays, ChevronRight, SquarePen } from 'lucide-react';
import React from 'react';

interface Props {
    fetchItems?: IGetShoppingItemsResponse['data'];
    fetchCategories?: IGetShoppingCategoriesResponse['data'];
}

const ShoppingListPage: React.FC<Props> = ({ fetchItems, fetchCategories }) => {
    const {
        items: storeItems,
        setServerItems,
        setItems: setStoreItems,
        setCategories: setStoreCategories,
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
        if (fetchCategories) {
            setStoreCategories(fetchCategories);
        }
    }, [fetchItems]);

    React.useEffect(() => {
        setIsLoading(isLoadingCategories || isLoadingItems);
    }, [isLoadingCategories, isLoadingItems]);

    return (
        <>
            {isLoading && <LoadingAnimation />}
            {/* ヘッダー */}
            <Header title="買い物リスト">
                <div className="flex gap-x-4">
                    {/* TODO: 実装 */}
                    <TextButton colorVariant="accent" onClick={() => {}}>
                        <CalendarDays size={20} />
                        献立から追加
                    </TextButton>
                    <TextButton
                        colorVariant="gray"
                        onClick={() =>
                            openDialog('itemSetting', {
                                item: undefined,
                                editMode: SHOPPING_ITEM_EDIT_MODE.CREATE,
                            })
                        }>
                        <SquarePen size={20} />
                        テキストから追加
                    </TextButton>
                </div>
            </Header>
            {/* メインコンテンツ */}
            <main className="p-5">
                <div className="pb-12 flex flex-col gap-y-7">
                    {/* 買い物リスト */}
                    <ShoppingList items={storeItems} />
                    <TextButton
                        onClick={() => {
                            openDialog('categorySetting', undefined);
                        }}>
                        カテゴリーの追加・編集
                        <ChevronRight size={20} />
                    </TextButton>
                </div>
            </main>
            {/* アイテム追加・編集ダイアログ */}
            <ShoppingItemSettingDialog />
            {/* カテゴリー設定ダイアログ */}
            <ShoppingCategorySettingDialog />
        </>
    );
};

export default ShoppingListPage;
