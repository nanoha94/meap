import React from 'react';
import { notFound } from 'next/navigation';
import { Suspense } from 'react';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import PlanEditPage from '@/pages/plan/edit/PlanEditPage';
import { IGetMealPlanShowResponse } from '@/types';

interface Props {
    params: Promise<{ id: string }>;
}

interface PageWithDataProps {
    id: string;
}
const PageWithData = async ({ id }: PageWithDataProps) => {
    const { data: mealPlan, errorMessage } = await fetchData<IGetMealPlanShowResponse>(`/meal-plans/${id}`);

    if (errorMessage || !mealPlan) {
        notFound();
    }

    return (
        <PlanEditPage
            fetchMealPlan={mealPlan.data}
            errorMessage={errorMessage}
        />
    );
};

const Page = async ({ params }: Props) => {
    const { id } = await params;
    return (
        <Suspense fallback={<Loading />}>
            <PageWithData id={id} />
        </Suspense>
    );
};

export default Page;