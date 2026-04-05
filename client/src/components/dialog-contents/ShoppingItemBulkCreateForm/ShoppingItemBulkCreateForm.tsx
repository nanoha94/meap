"use client";

import React from "react";

import { Button, VerticalRowField } from "@/components";
import { StyledSelect } from "@/components/form-fields";
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT } from "@/constants";
import { useDialog } from "@/hooks";
import { useMealStore } from "@/models/meal";
import { useShoppingStore } from "@/models/shopping";
import { useShoppingItemBulkCreateForm } from "@/models/shopping/hooks/useShoppingItemBulkCreateForm";
import DayMealPlanSection from "./DayMealPlanSection";
import MealPlanSearchForm from "./MealPlanSearchForm";
import { ShoppingItemBulkCreateFormData } from "@/models/shopping/types";
import { Control } from "react-hook-form";
import { IShoppingCategory } from "@/types";

const ShoppingItemBulkCreateForm = () => {
    // store
    const mealCategories = useMealStore(state => state.mealCategories);
    const categories = useShoppingStore(state => state.categories);

    // hook
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const {
        control,
        isDisabledSendButton,
        mealPlans,
        dateList,
        isChecked,
        handleChange,
        handleSelectAll,
        handleUnselectAll,
        onSubmit,
        searchMealPlans,
        updateDateList,
    } = useShoppingItemBulkCreateForm();

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({
            isCheckBeforeClose: !isDisabledSendButton,
            footer: <FormFooter control={control} categories={categories} isDisabledSendButton={isDisabledSendButton} closeDialog={closeDialog} />,
        });
    }, [isDisabledSendButton, updateCurrentDialogConfig, control, categories, closeDialog]);


    return (
        <form className="flex flex-col" onSubmit={onSubmit}>
            <div className="p-5 flex-1">
                <div className="pb-5 border-b border-gray-border">
                    {/* 献立プランの検索フォーム */}
                    <MealPlanSearchForm search={searchMealPlans} updateDateList={updateDateList} />
                </div>
            </div>
            <div className="mb-5 flex flex-col gap-y-8">
                {dateList.map((date) => {
                    const dateKey = date.format("YYYY-MM-DD");
                    return (
                        <DayMealPlanSection
                            key={dateKey}
                            date={date}
                            mealPlan={mealPlans.find((p) => p.date === dateKey)}
                            mealCategories={mealCategories}
                            isChecked={isChecked}
                            handleChange={handleChange}
                            handleSelectAll={handleSelectAll}
                            handleUnselectAll={handleUnselectAll}
                        />
                    );
                })}
            </div>
        </form>

    );
};

export default ShoppingItemBulkCreateForm;

interface FormFooterProps {
    control: Control<ShoppingItemBulkCreateFormData>;
    categories: IShoppingCategory[];
    isDisabledSendButton: boolean;
    closeDialog: () => void;
}

const FormFooter = ({ control, categories, isDisabledSendButton, closeDialog }: FormFooterProps) => {
    return <div className="p-3 w-full flex flex-wrap gap-4 items-end bg-white rounded-b-xl" style={{ boxShadow: '0px -5px 8px 0 rgba(0, 0, 0, 10%)' }}>
        <div className=" w-full h-fit flex flex-col sm:flex-row gap-6 sm:items-end justify-between">
            <div className="sm:max-w-[260px] min-w-0 flex-1">
                <VerticalRowField
                    control={control}
                    name="categoryId"
                    label="カテゴリーを選択して追加">
                    {({ value, onChange }) => (
                        <StyledSelect
                            value={value as string}
                            name="categoryId"
                            options={categories}
                            isShowPlaceholder={false}
                            onChange={onChange}
                        />
                    )}
                </VerticalRowField>
            </div>
            <div className="mx-auto sm:mr-0 sm:max-w-[320px] w-full flex gap-x-6">
                <Button
                    type={BUTTON_TYPE.BUTTON}
                    colorVariant={COLOR_VARIANT.GRAY}
                    variant={BUTTON_VARIANT.OUTLINED}
                    onClick={() => closeDialog()}>
                    戻る
                </Button>
                <Button type={BUTTON_TYPE.SUBMIT} disabled={isDisabledSendButton}>
                    追加
                </Button>
            </div>
        </div>
    </div>;
};