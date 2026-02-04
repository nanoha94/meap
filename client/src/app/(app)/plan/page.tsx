import React from 'react';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import PlanCalendarPage from '@/pages/plan/calendar/PlanCalendarPage';
import { IGetMealPlanIndexResponse } from '@/types';


export const metadata = {
    title: 'Laravel - Plan',
};

const PlanPageWithData = async () => {
    const { data: mealPlans, errorMessage } = await fetchData<IGetMealPlanIndexResponse>('/meal-plans');

    return (
        <PlanCalendarPage
            fetchMealPlans={mealPlans?.data ?? []}
            errorMessage={errorMessage} />
    );
};

const Plan = () => {
    return (
        <React.Suspense fallback={<Loading />}>
            <PlanPageWithData />
        </React.Suspense>
    );
};

export default Plan;
