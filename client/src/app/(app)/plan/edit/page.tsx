import { Loading } from "@/components";
import { API_STATUS_CODE } from "@/constants";
import { fetchData } from "@/lib/apiClient";
import PlanEditPage from "@/pages/plan/edit/PlanEditPage";
import { IGetMealPlanShowResponse } from "@/types";
import { notFound } from "next/navigation";
import React from "react";

interface Props {
    searchParams: Promise<{ date?: string }>;
}

interface PageWithDataProps {
    date: string;
}

const PageWithData = async ({ date }: PageWithDataProps) => {
    const { data: mealPlan, errorMessage, statusCode } =
        await fetchData<IGetMealPlanShowResponse>(`/meal-plans/${date}`, {
            suppressNotFoundLog: true,
        });

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
            {/* // プリレンダ時や date 未指定時は今日の日付を使用（Invalid time value を防ぐ） */}
            <PageWithData date={date ?? new Date().toISOString().slice(0, 10)} />
        </React.Suspense>
    );
};


export default Page;