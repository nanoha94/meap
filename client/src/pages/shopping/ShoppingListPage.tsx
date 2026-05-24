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
    ShoppingListHandle,
    useShoppingStore,
} from '@/models/shopping';
import { IShoppingItem } from '@/types';
import ShoppingListPageHeader from './ShoppingListPageHeader';

interface Props {
    fetchItems?: IShoppingItem[];
    errorMessage?: string;
}

const ShoppingListPage: React.FC<Props> = ({ fetchItems, errorMessage }) => {
    // store
    const setServerItems = useShoppingStore(state => state.setServerItems);
    const setStoreItems = useShoppingStore(state => state.setItems);

    // hook
    const { addSnackbar } = useSnackbars();
    const { openDialog } = useDialog();
    const shoppingListRef = React.useRef<ShoppingListHandle>(null);

    /**
     * カテゴリー設定ダイアログを開く
     * @returns void
     */
    const handleOpenCategorySettingDialog = async () => {
        await shoppingListRef.current?.syncPendingItems();
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
    }, [fetchItems, setStoreItems, setServerItems]);

    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage, addSnackbar]);

    return (
        <>
            <ShoppingListPageHeader />
            {/* 画面下部にスクロールバーを表示したいので、ここでoverflow-x-autoを指定 */}
            <main className="pt-5 w-full pb-[60px] h-[calc(100dvh-140px)] md:h-[calc(100dvh-60px)] overflow-x-auto">
                <div className="px-5 md:px-10">
                    {/* 買い物リスト */}
                    <div className="mb-7">
                        <ShoppingList ref={shoppingListRef} />
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
