import { IMealPlan } from "@/types";

// 献立プラン編集画面のフォーム型
// dateはURLクエリパラメータで管理するため、ここでは不要
export type MealPlanEditFormData = Omit<IMealPlan, 'date'>;

// 献立プラン一覧取得画面のフォーム型
export interface MealPlanFilterFormData {
    dateFrom?: string;
    dateTo?: string;
    includeIngredients?: boolean;
}