import React from 'react';
import dayjs from 'dayjs';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { getDateFromSearchParams } from '@/models/meal';
import PlanCalendarPage from '@/pages/plan/calendar/PlanCalendarPage';
import { IGetMealPlanIndexResponse } from '@/types';

export const metadata = {
    title: 'Laravel - Plan',
};

interface PlanPageWithDataProps {
    searchParams: Promise<{ date?: string }> | { date?: string };
}

const PlanPageWithData = async ({ searchParams }: PlanPageWithDataProps) => {
    const resolvedParams = await Promise.resolve(searchParams);
    const { date, year, month } = getDateFromSearchParams(resolvedParams);
    const dateFrom = dayjs(date).startOf('month').format('YYYY-MM-DD');
    const dateTo = dayjs(date).endOf('month').format('YYYY-MM-DD');
    const { data: mealPlans, errorMessage } =
        await fetchData<IGetMealPlanIndexResponse>(
            `/meal-plans?date_from=${dateFrom}&date_to=${dateTo}`,
        );

    return (
        <PlanCalendarPage
            key={date}
            fetchMealPlans={mealPlans?.data ?? []}
            errorMessage={errorMessage}
            year={year}
            month={month}
            date={date}
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
