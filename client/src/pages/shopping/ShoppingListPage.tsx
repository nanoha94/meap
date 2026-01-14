'use client';
import { Dialog, TextButton } from '@/components/common';
import {
    AddShoppingItemButton,
    ShoppingCategorySettingForm,
    ShoppingItemSettingDialog,
    ShoppingList,
} from '@/models/shopping/components';
import { useShoppingStore } from '@/models/shopping/hooks';
import { useGlobalStore } from '@/stores';
import { IShoppingCategory, IShoppingItem } from '@/types/api';
import { ChevronRight } from 'lucide-react';
import React from 'react';

interface Props {
    fetchItems?: IShoppingItem[];
    fetchCategories?: IShoppingCategory[];
}

const ShoppingListPage: React.FC<Props> = ({ fetchItems, fetchCategories }) => {
    const {
        setServerItems,
        setItems: setStoreItems,
        setCategories: setStoreCategories,
        isLoadingCategories,
        isLoadingItems,
    } = useShoppingStore();
    const { setIsLoading } = useGlobalStore();
    const [isOpenCategorySettingDialog, setIsOpenCategorySettingDialog] =
        React.useState<boolean>(false);

    const handleOpenCategorySettingDialog = () => {
        setIsOpenCategorySettingDialog(true);
    };

    const handleCloseCategorySettingDialog = () => {
        setIsOpenCategorySettingDialog(false);
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

    return (
        <>
            {/* メインコンテンツ */}
            <div className="p-5 pb-[60px] md:px-10">
                <div className="pb-12 flex flex-col gap-y-7">
                    {/* 買い物リスト */}
                    <ShoppingList />
                    <TextButton
                        colorVariant="secondary"
                        onClick={handleOpenCategorySettingDialog}>
                        カテゴリーの追加・編集
                        <ChevronRight size={20} />
                    </TextButton>
                </div>
                <AddShoppingItemButton />
            </div>
            {/* アイテム追加・編集ダイアログ */}
            <ShoppingItemSettingDialog />
            {/* カテゴリー設定ダイアログ */}
            <Dialog
                title="買い物カテゴリ―設定"
                isOpen={isOpenCategorySettingDialog}
                onClose={handleCloseCategorySettingDialog}>
                <ShoppingCategorySettingForm
                    onClose={handleCloseCategorySettingDialog}
                />
            </Dialog>
        </>
    );
};

export default ShoppingListPage;
