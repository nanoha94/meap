'use client';
import React from 'react';
import dayjs, { Dayjs } from 'dayjs';
import 'dayjs/locale/ja';
import { useRouter } from 'next/navigation';

import { Header, MonthlyCalendar, TextButton } from '@/components';
import { getDayOfWeekTextColor } from '@/constants/calendar';
import { useSnackbars } from '@/hooks';
import { IMealPlan } from '@/types';
import { MealCard, useMealStore } from '@/models/meal';
import { COLOR_VARIANT } from '@/constants';
import { ChevronRight } from 'lucide-react';

interface Props {
    fetchMealPlans: IMealPlan[];
    errorMessage?: string;
    year: number;
    month: number;
    date: string;
}

const PlanCalendarPage = ({ fetchMealPlans, errorMessage, year, month, date }: Props) => {
    // store
    const mealCategories = useMealStore(state => state.mealCategories);
    const setMealPlans = useMealStore(state => state.setMealPlans);

    // hook
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const [selectedDate, setSelectedDate] = React.useState<Dayjs>(() => dayjs(date));

    /**
     * 献立表のドットを生成
     * @returns string[][]
     */
    const dots = React.useMemo(() => {
        const daysInMonth = 31;
        const result: string[][] = Array.from({ length: daysInMonth }, () => []);
        (fetchMealPlans ?? []).forEach(mealPlan => {
            const day = dayjs(mealPlan.date).date();
            const index = day - 1;
            if (index >= 0 && index < daysInMonth) {
                const colors = mealPlan?.meals?.map(meal => mealCategories?.find(c => c.id === meal.categoryId)?.colorCodeHex ?? '');
                result[index] = Array.from(new Set(colors));
            }
        });
        return result;
    }, [fetchMealPlans, mealCategories]);

    /**
     * 選択された日付の献立表を取得
     * @returns IMealPlan | undefined
     */
    const mealPlan: IMealPlan | undefined = React.useMemo(() => {
        return fetchMealPlans?.find(v => v.date === selectedDate.format('YYYY-MM-DD'));
    }, [fetchMealPlans, selectedDate]);

    /**
     * 編集ページのパスを生成
     * @returns string
     */
    const editPagePath = React.useMemo(() => {
        return `/plan/edit?date=${selectedDate.format('YYYY-MM-DD')}`;
    }, [selectedDate]);

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
            <main className="mx-auto pb-[60px] lg:px-10 max-w-[1000px]">
                <MonthlyCalendar
                    dots={dots}
                    year={year}
                    month={month}
                    onMonthChange={(y, m) => {
                        const newDate = dayjs().year(y).month(m - 1).date(1).format('YYYY-MM-DD');
                        router.push(`/plan?date=${newDate}`);
                    }}
                    selectedDate={selectedDate}
                    onDateSelect={(date) => {
                        setSelectedDate(prev => {
                            const resolved = typeof date === 'function' ? date(prev) : date;
                            window.history.replaceState(null, '', `/plan?date=${resolved.format('YYYY-MM-DD')}`);
                            return resolved;
                        });
                    }}
                />
                <div className="pt-5 flex flex-col gap-y-3">
                    <div className="px-3 flex items-center justify-between gap-x-1"><div className={`text-base font-bold ${getDayOfWeekTextColor(selectedDate.day())}`}>{selectedDate.locale('ja').format('MM/DD')}<span className="ml-1 text-xs">{selectedDate.locale('ja').format('(ddd)')}</span>
                    </div>
                        <TextButton
                            href={editPagePath}
                            colorVariant={COLOR_VARIANT.SECONDARY}>
                            献立を作成・編集
                            <ChevronRight size={20} />
                        </TextButton>
                    </div>
                    {!mealPlan
                        ? <p className="px-3">登録されている献立はありません。</p>
                        : mealCategories?.map(v => {
                            const mealPlanItems = mealPlan.meals.filter(m => m.categoryId === v.id);
                            return mealPlanItems && mealPlanItems.length > 0 &&
                                <MealCard key={v.id} mealPlanId={mealPlan.id} mealPlanItems={mealPlanItems} mealCategory={v} editPagePath={editPagePath} />;
                        })
                    }
                </div>
            </main>
        </>
    );
};

export default PlanCalendarPage;
