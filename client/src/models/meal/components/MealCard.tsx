'use client';
import { Pencil, Trash2 } from "lucide-react";

import { MenuButton } from "@/components";
import { ActionButton, IMealCategory, IMealPlanItem } from "@/types";
import { useAlertDialog } from "@/hooks";
import RecipeCard from "./RecipeCard";
import { MEAL_ALERT_DIALOG_CONFIGS } from "../constants";
import { useMealApi } from "../hooks/useMealApi";

interface Props {
    mealPlanId: string;
    mealPlanItems: IMealPlanItem[];
    mealCategory: IMealCategory;
    editPagePath: string;
}

/**
 * 献立カード（表示専用）
 */
const MealCard = ({ mealPlanId, mealPlanItems, mealCategory, editPagePath }: Props) => {
    const { openAlertDialog } = useAlertDialog();
    const { deleteMeal } = useMealApi();
    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtonConfigs: ActionButton[] = [
        {
            label: '編集する',
            icon: <Pencil />,
            href: editPagePath,
        },
        {
            label: '削除する',
            icon: <Trash2 />,
            onClick: () => {
                openAlertDialog(MEAL_ALERT_DIALOG_CONFIGS.deleteItem(mealCategory.name), () => {
                    // mealPlanItemsは同じ値が入って来るので、先頭のidを使用
                    deleteMeal(mealPlanId, mealPlanItems[0].id);
                });
            },
        },
    ];

    return (
        <div className="pr-2 pl-3 pt-2 pb-4 md:px-5 md:py-4 flex flex-col gap-y-5 bg-white md:rounded shadow-card">
            <div className="flex items-center justify-between">
                <div
                    className="relative pl-4 text-xl before:content-[''] before:absolute before:top-1/2 before:left-0 before:translate-y-[-50%] before:block before:w-1 before:h-5/6 before:bg-[var(--category-color)] before:rounded-full"
                    style={{ ["--category-color" as string]: mealCategory.colorCodeHex }}
                >
                    {mealCategory.name}
                </div>
                {actionButtonConfigs.length > 0 && (
                    <MenuButton actionButtons={actionButtonConfigs} placement="top-right" />
                )}
            </div>
            <div className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] md:grid-cols-[repeat(auto-fill,_minmax(180px,_1fr))] gap-5">
                {mealPlanItems.map((v) => (
                    <RecipeCard key={v.recipeId} recipe={v} hasDeleteButton={false} />
                ))}
            </div>
        </div>
    );
};

export default MealCard;
