'use client';
import { TextButton } from '@/components/common';
import {
    AddShoppingItemButton,
    ShoppingList,
} from '@/models/shopping/components';
import { useShoppingStore } from '@/models/shopping/hooks';
import { useGlobalStore } from '@/stores';
import { IShoppingCategory, IShoppingItem } from '@/types/api';
import { ChevronRight } from 'lucide-react';
import React from 'react';
import { useSnackbars } from '@/hooks/useSnackbars';
import { useDialog } from '@/hooks/useDialog';
import ShoppingListPageHeader from './ShoppingListPageHeader';
import { COLOR_VARIANT } from '@/constants';
import { ShoppingCategoryEditForm } from '@/components/dialog-contents';

interface Props {
    fetchItems?: IShoppingItem[];
    fetchCategories?: IShoppingCategory[];
    errorMessage?: string;
}

const ShoppingListPage: React.FC<Props> = ({ fetchItems, fetchCategories, errorMessage }) => {
    const {
        setServerItems,
        setItems: setStoreItems,
        setCategories: setStoreCategories,
        isLoadingCategories,
        isLoadingItems,
    } = useShoppingStore();
    const { setIsLoading } = useGlobalStore();
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

    // カテゴリーをストアにセット
    React.useEffect(() => {
        if (fetchCategories) {
            setStoreCategories(fetchCategories);
        }
    }, [fetchCategories]);

    React.useEffect(() => {
        setIsLoading(isLoadingCategories || isLoadingItems);
    }, [isLoadingCategories, isLoadingItems]);

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
            <main>
                {/* メインコンテンツ */}
                <div className="p-5 pb-[60px] md:px-10">
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
                </div>
            </main>
        </>
    );
};

export default ShoppingListPage;
