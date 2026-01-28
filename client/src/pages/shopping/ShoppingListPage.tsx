'use client';
import { TextButton } from '@/components/common';
import {
    AddShoppingItemButton,
    ShoppingList,
} from '@/models/shopping/components';
import { useShoppingStore } from '@/models/shopping/hooks';
import { IShoppingItem } from '@/types/api';
import { ChevronRight } from 'lucide-react';
import React from 'react';
import { useSnackbars } from '@/hooks/useSnackbars';
import { useDialog } from '@/hooks/useDialog';
import ShoppingListPageHeader from './ShoppingListPageHeader';
import { COLOR_VARIANT } from '@/constants';
import { ShoppingCategoryEditForm } from '@/components/dialog-contents';

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
            children: () => <ShoppingCategoryEditForm />,
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
            <main className="p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto">
                <div className="pb-12 flex flex-col gap-y-7">
                    {/* 買い物リスト */}
                    <ShoppingList />
                    <TextButton
                        colorVariant={COLOR_VARIANT.SECONDARY}
                        onClick={handleOpenCategorySettingDialog}>
                        カテゴリーの追加・編集
                        <ChevronRight size={20} />
                    </TextButton>
                </div>
                <AddShoppingItemButton />
            </main>
        </>
    );
};

export default ShoppingListPage;
