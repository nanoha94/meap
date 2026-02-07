"use client";
import React from 'react';
import { Save, Trash2 } from 'lucide-react';
import 'react-datepicker/dist/react-datepicker.css';

import { Header, HeaderTextButton, StyledDatePicker } from '@/components';
import { BUTTON_TYPE, COLOR_VARIANT } from '@/constants';
import { MealCardField, useMealStore } from '@/models/meal';
import { ActionButton, IMealPlan } from '@/types';
import { FormProvider } from 'react-hook-form';
import { useMealPlanEditForm } from '@/models/meal/hooks/useMealPlanEditForm';
import { useSnackbars } from '@/hooks';

interface Props {
    fetchMealPlan?: IMealPlan;
    errorMessage?: string;
}

const PlanEditPage = ({ fetchMealPlan, errorMessage }: Props) => {
    const { mealCategories } = useMealStore();
    const { addSnackbar } = useSnackbars();
    const [selectedDate, setSelectedDate] = React.useState(new Date());
    const {
        control,
        methods,
        onSubmit
    } = useMealPlanEditForm(selectedDate.toISOString(), fetchMealPlan);


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
            <Header hasBackButton={true} leftContent={
                <div className="items-center gap-x-4 whitespace-nowrap w-[300px] hidden md:flex">
                    <StyledDatePicker value={fetchMealPlan?.date ? new Date(fetchMealPlan.date) : new Date()} onChange={(date) => setSelectedDate(date ?? new Date())} />
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
                        {mealCategories.map((v, index) => (
                            <MealCardField key={v.id} control={control} mealCategory={v} mealIndex={index} actionButtonConfigs={actionButtonConfigs} />
                        ))}
                    </form>
                </FormProvider>
            </main>
        </>
    );
};

export default PlanEditPage;