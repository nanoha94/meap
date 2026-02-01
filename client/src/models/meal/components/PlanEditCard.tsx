import { IMealCategory, IRecipeListItem } from "@/types";
import { Plus } from "lucide-react";
import React from "react";

interface Props {
    mealCategory: IMealCategory;
    recipes: IRecipeListItem[];
}

const PlanEditCard = ({ mealCategory, recipes }: Props) => {
    return (
        <div className="px-5 py-4 flex flex-col gap-y-5 bg-white rounded shadow-card">
            <div
                className="relative pl-4 text-xl before:content-[''] before:absolute before:top-1/2 before:left-0 before:translate-y-[-50%] before:block before:w-1 before:h-5/6 before:bg-[var(--category-color)] before:rounded-full"
                style={{ ["--category-color" as string]: mealCategory.colorCodeHex }}
            >
                {mealCategory.name}
            </div>
            <div className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] gap-5 ">
                {recipes.map(v => <div key={v.id}>{v.name}</div>)}
                <button type="button" className="relative ml-9 w-full h-auto aspect-[5/4] bg-gray-background border-dashed border rounded"><Plus size={24} strokeWidth={2} className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-main" /></button>
            </div>
        </div>
    );
};

export default PlanEditCard;