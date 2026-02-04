'use client';
import React from 'react';
import dayjs from 'dayjs';

import { Header, MonthlyCalendar } from '@/components';
import EmptyButton from '@/components/EmptyButton';
import { useSnackbars } from '@/hooks';
import { IMealPlan } from '@/types';
import { useMealStore } from '@/models/meal';

interface Props {
    fetchMealPlans: IMealPlan[];
    errorMessage?: string;
}

const PlanCalendarPage = ({ fetchMealPlans, errorMessage }: Props) => {
    const { addSnackbar } = useSnackbars();
    const { setMealPlans } = useMealStore();

    // const dots = [
    //     ['red', 'blue'],
    //     ['yellow'],
    //     [],
    //     [],
    //     [],
    //     ['blue'],
    //     ['red'],
    //     ['yellow'],
    // ];

    /**
     * 献立表のドットを生成
     * @returns string[][]
     */
    const dots = React.useMemo(() => {
        const daysInMonth = 31;
        const result: string[][] = Array.from({ length: daysInMonth }, () => []);
        fetchMealPlans.forEach(mealPlan => {
            const day = dayjs(mealPlan.date).date();
            const index = day - 1;
            if (index >= 0 && index < daysInMonth) {
                result[index] = mealPlan.meals.map(meal => meal.category.colorCodeHex);
            }
        });
        return result;
    }, [fetchMealPlans]);

    /**
        * 献立表をストアにセット
        * @returns void
        */
    React.useEffect(() => {
        if (fetchMealPlans) {
            setMealPlans(fetchMealPlans);
        }
    }, [fetchMealPlans]);


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
            <Header title="献立表" />
            <main className='pb-[60px] lg:px-10 max-w-[1000px] mx-auto'>
                <MonthlyCalendar dots={dots} />
                <div className="py-5 flex">
                    <EmptyButton href="/plan/new" />
                </div>
            </main>
        </>
    );
};

export default PlanCalendarPage;
