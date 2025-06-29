'use client';
import { Header, TextButton } from '@/components/common';
import {
    ShoppingCategorySettingDialog,
    ShoppingItemSettingDialog,
    ShoppingList,
} from '@/models/shopping/components';
import { useShoppingStore } from '@/models/shopping/hooks';
import { IGetShoppingItemsResponse } from '@/types/api';
import { CalendarDays, ChevronRight, SquarePen } from 'lucide-react';
import React from 'react';

interface Props {
    fetchItems?: IGetShoppingItemsResponse['data'];
}

const ShoppingLists: React.FC<Props> = ({ fetchItems }) => {
    const { items: storeItems, setItems: setStoreItems } = useShoppingStore();
    const { openDialog } = useShoppingStore();

    React.useEffect(() => {
        if (fetchItems) {
            setStoreItems(fetchItems);
        }
    }, [fetchItems]);

    return (
        <>
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
                        onClick={() => openDialog('itemSetting', undefined)}>
                        <SquarePen size={20} />
                        テキストから追加
                    </TextButton>
                </div>
            </Header>
            <div className="p-5">
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
            </div>
            {/* アイテム追加・編集ダイアログ */}
            <ShoppingItemSettingDialog />
            {/* カテゴリー設定ダイアログ */}
            <ShoppingCategorySettingDialog />
        </>
    );
};

export default ShoppingLists;
