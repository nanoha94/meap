import React from 'react';
import dayjs from 'dayjs';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { getYearMonthFromSearchParams } from '@/models/meal';
import PlanCalendarPage from '@/pages/plan/calendar/PlanCalendarPage';
import { IGetMealPlanIndexResponse } from '@/types';

export const metadata = {
    title: 'Laravel - Plan',
};

interface PlanPageWithDataProps {
    searchParams: Promise<{ year?: string; month?: string }> | { year?: string; month?: string };
}

const PlanPageWithData = async ({ searchParams }: PlanPageWithDataProps) => {
    const resolvedParams = await Promise.resolve(searchParams);
    const { year, month } = getYearMonthFromSearchParams(resolvedParams);
    const dateFrom = dayjs().year(year).month(month - 1).date(1).format('YYYY-MM-DD');
    const dateTo = dayjs(dateFrom).endOf('month').format('YYYY-MM-DD');
    const { data: mealPlans, errorMessage } =
        await fetchData<IGetMealPlanIndexResponse>(
            `/meal-plans?date_from=${dateFrom}&date_to=${dateTo}`,
        );

    return (
        <PlanCalendarPage
            fetchMealPlans={mealPlans?.data ?? []}
            errorMessage={errorMessage}
            year={year}
            month={month}
        />
    );
};

const Plan = async ({ searchParams }: PlanPageWithDataProps) => {
    return (
        <React.Suspense fallback={<Loading />}>
            <PlanPageWithData searchParams={searchParams} />
        </React.Suspense>
    );
};

export default Plan;
