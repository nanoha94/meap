import { MenuButton } from "@/components";
import { ActionButton, IMealCategory, IRecipeListItem } from "@/types";
import RecipeCard from "./RecipeCard";

interface Props {
    mealCategory: IMealCategory;
    recipes?: IRecipeListItem[];
    actionButtonConfigs?: ActionButton[];
}

/**
 * 献立カード（表示専用）
 */
const MealCard = ({ mealCategory, recipes = [], actionButtonConfigs = [] }: Props) => {
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
                {recipes.map((v) => (
                    <RecipeCard key={v.id} recipe={v} hasDeleteButton={false} />
                ))}
            </div>
        </div>
    );
};

export default MealCard;
