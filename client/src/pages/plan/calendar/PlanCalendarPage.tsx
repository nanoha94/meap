'use client';
import React from 'react';
import dayjs, { Dayjs } from 'dayjs';
import 'dayjs/locale/ja';
import { useRouter } from 'next/navigation';

import { Header, MonthlyCalendar } from '@/components';
import EmptyButton from '@/components/EmptyButton';
import { getDayOfWeekTextColor } from '@/constants/calendar';
import { useSnackbars } from '@/hooks';
import { ActionButton, IMealPlan } from '@/types';
import { MealCard, useMealStore } from '@/models/meal';
import { Pencil, Trash2 } from 'lucide-react';

interface Props {
    fetchMealPlans: IMealPlan[];
    errorMessage?: string;
    year: number;
    month: number;
}

const PlanCalendarPage = ({ fetchMealPlans, errorMessage, year, month }: Props) => {
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { setMealPlans } = useMealStore();
    const [selectedDate, setSelectedDate] = React.useState<Dayjs>(dayjs());

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

    const mealPlan = React.useMemo(() => {
        return fetchMealPlans.find(v => v.date === selectedDate.format('YYYY-MM-DD'));
    }, [fetchMealPlans, selectedDate]);

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

    /**
 * メニューボタン押下時に開くアクションボタン設定
 */
    const actionButtonConfigs: ActionButton[] = [
        {
            label: '編集する',
            icon: <Pencil />,
            href: `/plan/${mealPlan?.id}/edit`,
        },
        {
            label: '削除する',
            icon: <Trash2 />,
            onClick: () => {
                // openAlertDialog(SHOPPING_ALERT_DIALOG_CONFIGS.deleteItem(name), () => {
                //     deleteShoppingItems([id]);
                // });
                // TODO: 実装
            },
        },
    ];

    return (
        <>
            <Header title="献立表" />
            <main className='pb-[60px] lg:px-10 max-w-[1000px] mx-auto'>
                <MonthlyCalendar
                    dots={dots}
                    year={year}
                    month={month}
                    onMonthChange={(y, m) => router.push(`/plan?year=${y}&month=${m}`)}
                    selectedDate={selectedDate}
                    onDateSelect={setSelectedDate}
                />
                <div className="py-5 flex flex-col gap-y-3">
                    <div className={`px-3 text-base font-bold ${getDayOfWeekTextColor(selectedDate.day())}`}>{selectedDate.locale('ja').format('MM/DD')}<span className="ml-1 text-xs">{selectedDate.locale('ja').format('(ddd)')}</span>
                    </div>
                    {mealPlan?.meals.map(v => <MealCard key={v.id} mealCategory={v.category} recipes={v.recipes} actionButtonConfigs={actionButtonConfigs} />)}
                    <div className="px-3 lg:px-0"><EmptyButton href={mealPlan?.id ? `/plan/${mealPlan.id}/edit` : "/plan/new"} /></div>
                </div>
            </main>
        </>
    );
};

export default PlanCalendarPage;
