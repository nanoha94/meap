import { Loading } from "@/components";
import { API_STATUS_CODE } from "@/constants";
import { fetchData } from "@/lib/apiClient";
import PlanEditPage from "@/pages/plan/edit/PlanEditPage";
import { IGetMealPlanShowResponse } from "@/types";
import { notFound } from "next/navigation";
import React from "react";

interface Props {
    searchParams: {
        date: string;
    };
}

interface PageWithDataProps {
    date: string;
}

const PageWithData = async ({ date }: PageWithDataProps) => {
    const { data: mealPlan, errorMessage, statusCode } = await fetchData<IGetMealPlanShowResponse>(`/meal-plans/${date}`);

    // 404は「その日の献立が未登録」なので空の編集画面（新規作成）を表示する
    const isNotFound = statusCode === API_STATUS_CODE.NOT_FOUND;
    if (errorMessage && !isNotFound) {
        notFound();
    }

    return (
        <PlanEditPage
            selectedDate={date}
            fetchMealPlan={mealPlan?.data}
            errorMessage={isNotFound ? undefined : errorMessage}
        />
    );
};


const Page = async ({ searchParams }: Props) => {
    const { date } = await searchParams;

    return (
        <React.Suspense fallback={<Loading />}>
            <PageWithData date={date} />
        </React.Suspense>
    );
};


export default Page;