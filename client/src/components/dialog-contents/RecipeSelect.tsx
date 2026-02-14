"use client";
import React from "react";
import { CirclePlus, SlidersHorizontal } from "lucide-react";
import Image from "next/image";
import { useRouter } from "next/navigation";

import { useRecipeStore } from "@/models/recipe";
import { IRecipeListItem } from "@/types";
import { BUTTON_TYPE, colors } from "@/constants";
import { TextButton } from "@/components";
import { useDialog } from "@/hooks";
import { StyledSelect } from "../form-fields";

interface Props {
    selectedRecipe: IRecipeListItem | null;
    onSelectedRecipeChange: (recipe: IRecipeListItem) => void;
}

const RecipeSelect = ({ selectedRecipe, onSelectedRecipeChange }: Props) => {
    const router = useRouter();
    const { closeDialog } = useDialog();
    const { recipes } = useRecipeStore();
    const [sortOption, setSortOption] = React.useState<string>('newest');

    /**
     * 並び替えオプション
     */
    const sortOptions = [
        { id: 'created_at_newest', name: '作成日が新しい順' },
        { id: 'created_at_oldest', name: '作成日が古い順' },
        { id: 'meal_plan_date_newest', name: '前回の献立日が新しい順' },
        { id: 'meal_plan_date_oldest', name: '前回の献立日が古い順' },
        { id: 'name_asc', name: '名前順' },
    ];

    return (
        <div className="flex flex-col gap-y-5">
            <div className="flex justify-between gap-x-5">
                {/* TODO: 絞り込み・並び替え実装 */}
                <button type="button" onClick={() => { }} className="flex items-center gap-x-2"><SlidersHorizontal color={colors.black} strokeWidth={1.5} />絞り込み</button><StyledSelect value={sortOption} options={sortOptions} onChange={e => setSortOption(e.target.value)} isShowPlaceholder={false} className="!w-auto" />
            </div>
            {recipes.length > 0 ? (
                <>
                    <div className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] gap-3">
                        {recipes.map((v) => (
                            <div
                                key={v.id}
                                className={`relative w-full text-left flex flex-col bg-white rounded-md overflow-hidden transition-colors cursor-pointer hover:bg-gray-light border-2 ${selectedRecipe?.id === v.id ? 'border-primary-main' : 'border-transparent'}`}
                                style={{ boxShadow: '1px 1px 5px rgba(0, 0, 0, 15%)' }}
                                onClick={() => onSelectedRecipeChange(v)}
                            >
                                <div className="w-full h-auto aspect-video object-cover bg-gray-background">
                                    {v.thumbnail && v.thumbnail.src && (
                                        <Image
                                            src={v.thumbnail.src}
                                            alt={v.name}
                                            width={v.thumbnail.width}
                                            height={v.thumbnail.height}
                                            className="w-full h-auto aspect-video object-cover rounded-t"
                                        />
                                    )}
                                </div>
                                <div className="p-2 flex flex-col gap-y-1">
                                    <div className="text-sm">{v.name}</div>
                                    <div className="text-xs">前回の献立日：</div>
                                </div>
                            </div>
                        ))}
                    </div>
                    <TextButton
                        type={BUTTON_TYPE.BUTTON}
                        onClick={() => { router.push('/recipe/new'); closeDialog(); }}
                        className="!pl-0 !border-none !bg-transparent hover:!bg-gray-light">
                        <CirclePlus size={20} />
                        料理/レシピを追加
                    </TextButton>
                </>
            ) : (
                <div className="flex flex-col gap-y-2">
                    <p>まだ料理/レシピが登録されていません。</p>
                    <TextButton
                        type={BUTTON_TYPE.BUTTON}
                        onClick={() => { router.push('/recipe/new'); closeDialog(); }}
                        className="!pl-0 !border-none !bg-transparent hover:!bg-gray-light">
                        <CirclePlus size={20} />
                        料理/レシピを追加
                    </TextButton>
                </div>
            )
            }
        </div >
    );
};

export default RecipeSelect;