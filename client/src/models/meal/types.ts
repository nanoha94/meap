import { IMealPlan } from "@/types";

// 献立プラン編集画面のフォーム型
// dateはuseStateで管理するため、ここでは不要
export type MealPlanEditFormData = Omit<IMealPlan, 'date'>;
