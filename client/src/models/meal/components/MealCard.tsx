import { MenuButton } from "@/components";
import EmptyButton from "@/components/EmptyButton";
import { colors } from "@/constants";
import { ActionButton, IMealCategory, IRecipeListItem } from "@/types";
import { GripVertical, ImageIcon, Trash2 } from "lucide-react";
import Image from "next/image";
import React from "react";

interface Props {
    mealCategory: IMealCategory;
    recipes: IRecipeListItem[];
    isEdit?: boolean;
    actionButtonConfigs?: ActionButton[];
}

/**
 * 献立カードコンポーネント
 * @param mealCategory 献立カテゴリ
 * @param recipes レシピリスト
 * @param isEdit 編集モード
 * @param actionButtonConfigs アクションボタン設定
 * @returns 
 */
const MealCard = ({ mealCategory, recipes, isEdit = false, actionButtonConfigs = [] }: Props) => {
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
            <div className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] md:grid-cols-[repeat(auto-fill,_minmax(180px,_1fr))] gap-5">
                {recipes.map(v =>
                    isEdit ?
                        (
                            <div key={v.id} className="flex gap-x-2">
                                <GripVertical color={colors.gray.main} className="pt-1" />
                                <RecipeCard recipe={v} isEdit={isEdit} />
                            </div>
                        )
                        :
                        (
                            <RecipeCard key={v.id} recipe={v} isEdit={isEdit} />

                        )
                )}
                {isEdit && <EmptyButton type="button" className="ml-8 w-[calc(100%-32px)] !h-auto aspect-[4/3]" />}
            </div>
        </div >
    );
};

export default MealCard;

/**
 * レシピカードコンポーネント
 * @param recipe レシピ
 * @param isEdit 編集モード
 */
interface RecipeCardProps {
    recipe: IRecipeListItem;
    isEdit?: boolean;
}

const RecipeCard = ({ recipe, isEdit = false }: RecipeCardProps) => {
    return (
        <div className="relative flex-1">
            <div className="relative w-full h-auto aspect-[4/3] bg-gray-light rounded shadow-card md:rounded-lg overflow-hidden">
                {recipe.thumbnail ? (
                    <Image
                        src={recipe.thumbnail.src}
                        alt={recipe.name}
                        width={recipe.thumbnail.width}
                        height={recipe.thumbnail.height}
                        className="absolute top-0 left-0 object-cover"
                    />
                ) : (
                    <ImageIcon
                        size={40}
                        strokeWidth={1.5}
                        className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-main"
                    />
                )}

                <div className="absolute bottom-0 left-0 px-2 pt-2 pb-1 w-full bg-gradient-to-b from-white/0 via-white/70 to-white">
                    <span className="text-xs font-bold">{recipe.name}</span>
                </div>
            </div>
            {isEdit && <button onClick={() => { }} className="absolute -top-2.5 -right-2.5 p-1 appearance-none rounded-full bg-alert-main transition-colors hover:bg-alert-light">
                <Trash2
                    color={colors.white}
                    size={20}
                />
            </button>}
        </div>
    );
};
