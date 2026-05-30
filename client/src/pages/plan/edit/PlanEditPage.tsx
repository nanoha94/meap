"use client";
import { DndContext, DragEndEvent, DragOverlay, DragStartEvent, rectIntersection } from '@dnd-kit/core';
import { arrayMove } from '@dnd-kit/sortable';
import dayjs from 'dayjs';
import { Save, Trash2 } from 'lucide-react';
import { useRouter } from 'next/navigation';
import React from 'react';
import 'react-datepicker/dist/react-datepicker.css';
import { FormProvider } from 'react-hook-form';

import { Header, HeaderTextButton, StyledDatePicker } from '@/components';
import { BUTTON_TYPE, COLOR_VARIANT, LINK_TO } from '@/constants';
import { useAlertDialog, useItemAndCategoryDnd, useNavigationGuard, useSnackbars } from '@/hooks';
import { MealCardField, RecipeCard, useMealStore, useMealPlanEditForm, useMealPlanApi } from '@/models/meal';
import { useGlobalStore } from '@/stores';
import { ActionButton, IMealPlan, IMealPlanItem } from '@/types';
import { getInsertIndexForCategory, getItemsInCategory } from '@/utils';
import { MEAL_ALERT_DIALOG_CONFIGS } from '@/models/meal/constants';

interface Props {
    selectedDate: string;
    fetchMealPlan?: IMealPlan;
    errorMessage?: string;
}

const PlanEditPage = ({ selectedDate, fetchMealPlan, errorMessage }: Props) => {
    // store
    const resetLoadingCount = useGlobalStore(state => state.resetLoadingCount);
    const mealCategories = useMealStore(state => state.mealCategories);

    // hook
    const router = useRouter();
    const { openAlertDialog } = useAlertDialog(); const { deleteMealPlan } = useMealPlanApi();
    const { addSnackbar } = useSnackbars();
    const { methods, isDisabledSendButton, onSubmit, fields, insert, replace, remove } = useMealPlanEditForm(selectedDate, fetchMealPlan);
    const [tmpItems, setTmpItems] = React.useState<IMealPlanItem[]>([]);
    const [isPlanItemDragging, setIsPlanItemDragging] = React.useState(false);
    useNavigationGuard(!isDisabledSendButton);

    /**
     * ヘッダーメニューボタン押下時に開くアクションボタン設定
     */
    const headerActionButtonConfigs: ActionButton[] = [
        {
            label: '削除する',
            icon: <Trash2 size={20} strokeWidth={2} />,
            onClick: () =>
                openAlertDialog(MEAL_ALERT_DIALOG_CONFIGS.deleteItem(`${selectedDate}の献立すべて`), async () => {
                    const success = await deleteMealPlan(fetchMealPlan?.id ?? '');
                    if (success) router.push(LINK_TO.PLAN.TOP);
                }),
            color: COLOR_VARIANT.ALERT,
            disabled: !fetchMealPlan?.id,

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
        [tmpItems, replace],
    );

    const {
        sensors,
        activeItem,
        handleDragStart: dndHandleDragStart,
        handleDragOver,
        handleDragEnd: dndHandleDragEnd,
    } = useItemAndCategoryDnd({
        currentItems: isPlanItemDragging ? tmpItems : fields,
        categories: mealCategories,
        itemIdKey: 'recipeId',
        onDragOver: customHandleDragOver,
        onDragEnd: customHandleDragEnd,
    });

    const handleDragStart = React.useCallback(
        (event: DragStartEvent) => {
            setTmpItems(fields);
            setIsPlanItemDragging(true);
            dndHandleDragStart(event);
        },
        [dndHandleDragStart, fields],
    );

    const handleDragEnd = React.useCallback(
        (event: DragEndEvent) => {
            dndHandleDragEnd(event);
            setIsPlanItemDragging(false);
        },
        [dndHandleDragEnd],
    );

    /**
     * 日付変更時の処理
     * @param date 日付
     */
    const handleChangeDate = (date: Date) => {
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
    }, [errorMessage, addSnackbar]);

    /**
     * ページ遷移時の処理
     * ローディングカウンターをリセット
     */
    React.useEffect(() => {
        resetLoadingCount();
    }, [selectedDate, resetLoadingCount]);

    return (
        <>
            <Header hasBackButton={true} onBackClick={() => router.back()} leftContent={
                <div className="items-center gap-x-4 whitespace-nowrap w-[300px] hidden md:flex">
                    <StyledDatePicker
                        value={(() => {
                            const d = new Date(selectedDate);
                            return Number.isNaN(d.getTime()) ? new Date() : d;
                        })()}
                        onChange={handleChangeDate}
                    />
                </div>
            } rightContent={
                <HeaderTextButton type={BUTTON_TYPE.SUBMIT} form="plan-edit-form" colorVariant={COLOR_VARIANT.SECONDARY} disabled={isDisabledSendButton}>
                    <Save size={20} strokeWidth={2} />
                    保存
                </HeaderTextButton >}
                actionButtons={headerActionButtonConfigs}
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
                                const currentItems = isPlanItemDragging ? tmpItems : fields;
                                const items = getItemsInCategory(currentItems, category.id);
                                const itemsKey = items.map(item => item.recipeId).join(',');
                                return (
                                    <MealCardField
                                        key={`${category.id}-${itemsKey}`}
                                        mealCategory={category}
                                        mealPlanItems={items}
                                        addItem={(item: IMealPlanItem) => insert(getInsertIndexForCategory(fields, mealCategories, category.id), item)}
                                        deleteItem={(item: IMealPlanItem) => remove(fields.indexOf(item))}
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