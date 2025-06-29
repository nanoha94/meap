'use client';
import React from 'react';
import {
    CalendarDays,
    ChevronRight,
    LoaderCircle,
    SquarePen,
} from 'lucide-react';
import { Header, TextButton } from '@/components/common';
import { colors } from '@/constants/colors';
import {
    ShoppingCategorySettingDialog,
    ShoppingItemSettingDialog,
    ShoppingList,
} from '@/models/shopping/components';
import {
    useShoppingCategories,
    useShoppingItems,
} from '@/models/shopping/hooks';

const Page = () => {
    const {
        isLoading,
        storeData: { items: storeItems },
        fetchShoppingItems,
    } = useShoppingItems();

    const { storeData } = useShoppingCategories();

    const [isShowLoading, setIsShowLoading] = React.useState(false);

    // カテゴリー変更時の処理
    // TODO: アイテム追加時もローディングアニメーション表示したい
    React.useEffect(() => {
        if (
            storeData.categories &&
            storeItems &&
            Array.isArray(storeItems) &&
            JSON.stringify(storeData.categories) !==
                JSON.stringify(storeItems?.map(v => v.category))
        ) {
            // itemMutate();
            setIsShowLoading(true);
        }
    }, [storeData.categories, storeItems]);

    React.useEffect(() => {
        if (!isLoading && storeItems?.length > 0) setIsShowLoading(false);
    }, [isLoading]);

    // TODO: サーバーコンポーネント化したら、fetch関数に置き換え
    React.useEffect(() => {
        fetchShoppingItems();
    }, []);

    // TODO: あとで復活
    // const unCheckAllItems = () => {
    //     setItems(prev => {
    //         const newItems = { ...prev };
    //         Object.keys(prev).forEach(categoryId => {
    //             newItems[categoryId] = prev[categoryId].map(item => ({
    //                 ...item,
    //                 isChecked: false,
    //             }));
    //         });
    //         return newItems;
    //     });
    // };

    if (isShowLoading && isLoading) {
        return (
            <div className="py-5">
                <LoaderCircle
                    size={40}
                    color={colors.primary.main}
                    className="animate-spin mx-auto"
                />
            </div>
        );
    } else {
        return (
            <>
                <Header title="買い物リスト">
                    <div className="flex gap-x-4">
                        {/* TODO: 実装 */}
                        <TextButton colorVariant="accent" onClick={() => {}}>
                            <CalendarDays size={20} />
                            献立から追加
                        </TextButton>
                        <TextButton
                            colorVariant="gray"
                            onClick={() => {
                                // setIsOpenSettingItemDialog(true);
                            }}>
                            <SquarePen size={20} />
                            テキストから追加
                        </TextButton>
                    </div>
                </Header>
                <div className="p-5">
                    <div className="pb-12 flex flex-col gap-y-7">
                        <ShoppingList items={storeItems} />
                        <TextButton
                            onClick={() => {
                                // setIsOpenSettingCategoryDialog(true);
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
                {/* 買い物リストを空にするダイアログ */}
                {/* <AlertDialog
                    title="買い物リストを空にする"
                    description={
                        <>
                            <p className="text-center">
                                買い物リストに登録されているすべてのアイテムを削除しますか？
                            </p>
                            <p className="text-sm text-center">
                                ※固定化アイテムは削除されません
                            </p>
                        </>
                    }
                    isOpen={isOpenListEmptyDialog}
                    onClose={() => setIsOpenListEmptyDialog(false)}
                    actionButton={{
                        text: '削除',
                        onClick: () => {
                            deleteAllShoppingItems();
                            setIsOpenListEmptyDialog(false);
                        },
                    }}
                /> */}
            </>
        );
    }
};

export default Page;
