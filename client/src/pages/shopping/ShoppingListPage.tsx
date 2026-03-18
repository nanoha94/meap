'use client';
import React from 'react';
import { ChevronRight } from 'lucide-react';

import {
    ShoppingCategoryEditForm,
    TextButton,
} from '@/components';
import { COLOR_VARIANT } from '@/constants';
import { useDialog, useSnackbars } from '@/hooks';
import {
    ShoppingList,
    useShoppingStore,
} from '@/models/shopping';
import { IShoppingItem } from '@/types';
import ShoppingListPageHeader from './ShoppingListPageHeader';

interface Props {
    fetchItems?: IShoppingItem[];
    errorMessage?: string;
}

const ShoppingListPage: React.FC<Props> = ({ fetchItems, errorMessage }) => {
    const {
        setServerItems,
        setItems: setStoreItems,
    } = useShoppingStore();
    const { addSnackbar } = useSnackbars();
    const { openDialog } = useDialog();

    const handleOpenCategorySettingDialog = () => {
        openDialog({
            title: '買い物カテゴリ―設定',
            children: <ShoppingCategoryEditForm />,
        });
    };

    // アイテムをストアにセット
    React.useEffect(() => {
        if (fetchItems) {
            setStoreItems(fetchItems);
            setServerItems(fetchItems);
        }
    }, [fetchItems]);

    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage]);

    return (
        <>
            <ShoppingListPageHeader />
            <main className="pt-5 w-full h-[calc(100vh-60px)] overflow-auto">
                <div className="px-5 md:px-10 pb-[60px]">
                    {/* 買い物リスト */}
                    <div className="mb-7">
                        <ShoppingList />
                    </div>
                    <TextButton
                        colorVariant={COLOR_VARIANT.SECONDARY}
                        onClick={handleOpenCategorySettingDialog}>
                        カテゴリーの追加・編集
                        <ChevronRight size={20} />
                    </TextButton>
                </div>
            </main>
        </>
    );
};

export default ShoppingListPage;
