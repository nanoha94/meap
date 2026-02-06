import { MenuButton } from "@/components";
import EmptyButton from "@/components/EmptyButton";
import { ActionButton, IMealCategory, IRecipeListItem } from "@/types";
import { ImageIcon } from "lucide-react";
import Image from "next/image";
import React from "react";

interface Props {
    mealCategory: IMealCategory;
    recipes: IRecipeListItem[];
    isEdit: boolean;
    actionButtonConfigs: ActionButton[];
}

const MealCard = ({ mealCategory, recipes, isEdit, actionButtonConfigs }: Props) => {


    return (
        <div className="pr-2 pl-3 pt-2 pb-4 md:px-5 md:py-4 flex flex-col gap-y-5 bg-white md:rounded shadow-card">
            <div className="flex items-center justify-between"><div
                className="relative pl-4 text-xl before:content-[''] before:absolute before:top-1/2 before:left-0 before:translate-y-[-50%] before:block before:w-1 before:h-5/6 before:bg-[var(--category-color)] before:rounded-full"
                style={{ ["--category-color" as string]: mealCategory.colorCodeHex }}
            >
                {mealCategory.name}
            </div>
                {actionButtonConfigs.length > 0 && <MenuButton
                    actionButtons={actionButtonConfigs}
                    placement="top-right"
                />}</div>
            <div className="grid grid-cols-[repeat(auto-fill,_minmax(120px,_1fr))] md:grid-cols-[repeat(auto-fill,_minmax(180px,_1fr))] gap-5">
                {recipes.map(v => <div key={v.id}><div className={`relative w-full h-auto aspect-[4/3] bg-gray-light rounded overflow-hidden shadow-card md:rounded-lg`}>
                    {v.thumbnail ? (
                        <Image
                            src={v.thumbnail.src}
                            alt={v.name}
                            width={v.thumbnail.width}
                            height={v.thumbnail.height}
                            className="absolute top-0 left-0 w-full h-full object-cover"
                        />
                    ) : (
                        <ImageIcon
                            size={40}
                            strokeWidth={1.5}
                            className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-main"
                        />
                    )}
                    <div className="absolute bottom-0 left-0 px-2 pt-2 pb-1 w-full bg-gradient-to-b from-white/0 via-white/70 to-white"><span className="text-xs font-bold">{v.name}</span></div>
                </div></div>)}

                {isEdit && <EmptyButton type="button" className="ml-9 !h-auto aspect-[5/4]" />}
            </div>
        </div>
    );
};

export default MealCard;