"use client";
import React from 'react';
import { Save, Trash2 } from 'lucide-react';
import 'react-datepicker/dist/react-datepicker.css';

import { Header, HeaderTextButton, StyledDatePicker } from '@/components';
import { COLOR_VARIANT } from '@/constants';
import { PlanEditCard, useMealStore } from '@/models/meal';
import { ActionButton, IMealPlan } from '@/types';
import { FormProvider } from 'react-hook-form';
import { useMealPlanEditForm } from '@/models/meal/hooks/useMealPlanEditForm';
import { useSnackbars } from '@/hooks';

interface Props {
    fetchMealPlans?: IMealPlan[];
    errorMessage?: string;
}


const PlanEditPage = ({ fetchMealPlans, errorMessage }: Props) => {
    const { mealCategories } = useMealStore();
    const { addSnackbar } = useSnackbars();
    const [selectedDate, setSelectedDate] = React.useState(new Date());
    const {
        // control,
        methods,
        onSubmit
    } = useMealPlanEditForm(selectedDate.toISOString(), fetchMealPlans);


    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtons: ActionButton[] = [
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
                    <StyledDatePicker value={selectedDate} onChange={(date) => setSelectedDate(date ?? new Date())} />
                </div>
            } rightContent={
                <HeaderTextButton colorVariant={COLOR_VARIANT.SECONDARY}
                    onClick={() => { /* TODO: 保存処理 */ }}>
                    <Save size={20} strokeWidth={2} />
                    保存
                </HeaderTextButton>}
                actionButtons={actionButtons}
            />
            <main className="p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto">
                <FormProvider {...methods}>
                    <form onSubmit={onSubmit} className="flex flex-col gap-y-5 md:gap-y-8">
                        {mealCategories.map(v => <PlanEditCard key={v.id} mealCategory={v} recipes={fetchMealPlans?.find(plan => plan.mealCategory.id === v.id)?.recipes ?? []} />)}
                    </form>
                </FormProvider>
            </main>
        </>
    );
};

export default PlanEditPage;