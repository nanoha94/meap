"use client";
import { DndContext, DragOverlay, rectIntersection } from '@dnd-kit/core';
import { arrayMove } from '@dnd-kit/sortable';
import dayjs from 'dayjs';
import { Save, Trash2 } from 'lucide-react';
import { useRouter } from 'next/navigation';
import React from 'react';
import 'react-datepicker/dist/react-datepicker.css';
import { FormProvider } from 'react-hook-form';

import { Header, HeaderTextButton, StyledDatePicker } from '@/components';
import { BUTTON_TYPE, COLOR_VARIANT } from '@/constants';
import { useItemAndCategoryDnd, useSnackbars } from '@/hooks';
import { MealCardField, RecipeCard, useMealStore, useMealPlanEditForm } from '@/models/meal';
import { useGlobalStore } from '@/stores';
import { ActionButton, IMealPlan, IMealPlanItem } from '@/types';
import { getInsertIndexForCategory, getItemsInCategory } from '@/utils';

interface Props {
    selectedDate: string;
    fetchMealPlan?: IMealPlan;
    errorMessage?: string;
}

const PlanEditPage = ({ selectedDate, fetchMealPlan, errorMessage }: Props) => {
    const router = useRouter();
    const { incrementLoadingCount, resetLoadingCount } = useGlobalStore();
    const { mealCategories } = useMealStore();
    const { addSnackbar } = useSnackbars();
    const { methods, onSubmit, fields, insert, replace, remove } = useMealPlanEditForm(selectedDate, fetchMealPlan);
    const [tmpItems, setTmpItems] = React.useState<IMealPlanItem[]>([]);

    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtonConfigs: ActionButton[] = [
        {
            label: '削除する',
            icon: <Trash2 size={20} strokeWidth={2} />,
            onClick: () => {
                // TODO: 削除ダイアログ実装
            },
            color: COLOR_VARIANT.ALERT,
        },
    ];

    /**
  * ドラッグオーバー
  */
    const customHandleDragOver = React.useCallback(
        (
            activeId: string,
            activeItem: IMealPlanItem,
            overCategoryId: string,
        ) => {
            // 別カテゴリーへの移動の場合
            if (activeItem.categoryId !== overCategoryId) {
                // tmpItemsを更新
                setTmpItems(
                    tmpItems.map(v =>
                        v.recipeId === activeId
                            ? {
                                ...activeItem,
                                categoryId: overCategoryId,
                            }
                            : v,
                    ),
                );
            }
        },
        [tmpItems],
    );

    /**
     * ドラッグ終了
     */
    const customHandleDragEnd = React.useCallback(
        (activeIndex: number | undefined, overIndex: number | undefined) => {
            // 並び替えたtmpItemsを更新
            const array =
                activeIndex !== undefined && overIndex !== undefined
                    ? arrayMove(tmpItems, activeIndex, overIndex)
                    : tmpItems;
            replace(array);
        },
        [tmpItems],
    );

    const {
        sensors,
        activeId,
        activeItem,
        handleDragStart,
        handleDragOver,
        handleDragEnd,
    } = useItemAndCategoryDnd({
        currentItems: tmpItems,
        categories: mealCategories,
        itemIdKey: 'recipeId',
        onDragOver: customHandleDragOver,
        onDragEnd: customHandleDragEnd,
    });

    /**
     * 日付変更時の処理
     * @param date 日付
     */
    const handleChangeDate = (date: Date) => {
        incrementLoadingCount();
        router.push(`/plan/edit?date=${dayjs(date).format('YYYY-MM-DD')}`);
    };

    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage]);

    /**
     * ページ遷移時の処理
     * ローディングカウンターをリセット
     */
    React.useEffect(() => {
        resetLoadingCount();
    }, [selectedDate]);

    /**
  * ドラッグ中でない場合、tmpItemsをstoreItemsの内容で更新
  * @returns void
  */
    React.useEffect(() => {
        if (!activeId) {
            setTmpItems(fields);
        }
    }, [fields, activeId]);


    return (
        <>
            <Header hasBackButton={true} leftContent={
                <div className="items-center gap-x-4 whitespace-nowrap w-[300px] hidden md:flex">
                    <StyledDatePicker value={new Date(selectedDate)} onChange={handleChangeDate} />
                </div>
            } rightContent={
                <HeaderTextButton type={BUTTON_TYPE.SUBMIT} form="plan-edit-form" colorVariant={COLOR_VARIANT.SECONDARY}>
                    <Save size={20} strokeWidth={2} />
                    保存
                </HeaderTextButton>}
                actionButtons={actionButtonConfigs}
            />
            <main className="p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto">
                <FormProvider {...methods}>
                    <form id="plan-edit-form" onSubmit={onSubmit} className="flex flex-col gap-y-5 md:gap-y-8">
                        <DndContext
                            sensors={sensors}
                            collisionDetection={rectIntersection}
                            onDragStart={handleDragStart}
                            onDragEnd={handleDragEnd}
                            onDragOver={handleDragOver}>
                            {mealCategories.map(category => {
                                const items = getItemsInCategory(tmpItems, category.id);
                                const itemsKey = items.map(item => item.recipeId).join(',');
                                return (
                                    <MealCardField
                                        key={`${category.id}-${itemsKey}`}
                                        mealCategory={category}
                                        recipes={items}
                                        actionButtonConfigs={actionButtonConfigs}
                                        addItem={(item: IMealPlanItem) => insert(getInsertIndexForCategory(tmpItems, mealCategories, category.id), item)}
                                        deleteItem={(item: IMealPlanItem) => remove(tmpItems.indexOf(item))}
                                    />
                                );
                            })}
                            <DragOverlay>
                                {activeItem && <RecipeCard
                                    recipe={activeItem}
                                    isGrippable={true}
                                    hasDeleteButton={true}
                                    onDelete={() => { }}
                                />}
                            </DragOverlay>
                        </DndContext>
                    </form>
                </FormProvider>
            </main>
        </>
    );
};

export default PlanEditPage;