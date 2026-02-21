"use client";
import React from "react";
import { CirclePlus, SlidersHorizontal } from "lucide-react";
import Image from "next/image";
import { useRouter } from "next/navigation";

import { sortOptions, useRecipeApi, useRecipeStore } from "@/models/recipe";
import { IRecipeListItem } from "@/types";
import { BUTTON_TYPE, colors } from "@/constants";
import { TextButton } from "@/components";
import { useDialog } from "@/hooks";
import { StyledSelect } from "../form-fields";
import dayjs from "dayjs";

interface Props {
    selectedRecipe: IRecipeListItem | null;
    disabledRecipes: string[];
    onSelectedRecipeChange: (recipe: IRecipeListItem) => void;
    onConfirm: () => void;
}

const RecipeSelect = ({ selectedRecipe, disabledRecipes, onSelectedRecipeChange, onConfirm }: Props) => {
    const router = useRouter();
    const { closeDialog } = useDialog();
    const { recipes } = useRecipeStore();
    const { fetchRecipes } = useRecipeApi();
    const [sortOption, setSortOption] = React.useState<string>(sortOptions[0].id);

    /**
     * 並び替え処理
     * @param e SelectChangeEvent
     */
    const handleChangeSortOption = async (e: React.ChangeEvent<HTMLSelectElement>) => {
        const selected = e.target.value;
        setSortOption(selected);
        await fetchRecipes(selected);
    };

    return (
        <div className="flex flex-col gap-y-5">
            <div className="flex justify-between gap-x-5">
                {/* TODO: 絞り込み・並び替え実装 */}
                <button type="button" onClick={() => { }} className="flex items-center gap-x-2">
                    <SlidersHorizontal color={colors.black} strokeWidth={1.5} />絞り込み</button>
                <StyledSelect value={sortOption} options={sortOptions} onChange={handleChangeSortOption} isShowPlaceholder={false} className="!w-auto" />
            </div>
            {recipes.length > 0 ? (
                <>
                    <div className="grid grid-cols-[repeat(auto-fill,_minmax(160px,_1fr))] gap-3">
                        {recipes.map((v) => (
                            <button
                                key={v.id}
                                type="button"
                                disabled={disabledRecipes.includes(v.id)}
                                className={`relative w-full text-left flex flex-col bg-white rounded-md overflow-hidden transition-colors cursor-pointer hover:bg-gray-light border-2 disabled:opacity-50 disabled:pointer-events-none ${selectedRecipe?.id === v.id ? 'border-primary-main' : 'border-transparent'}`}
                                style={{ boxShadow: '1px 1px 5px rgba(0, 0, 0, 15%)' }}
                                onClick={() => onSelectedRecipeChange(v)}
                                onDoubleClick={onConfirm}
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
                                    <div className="text-xs">前回の献立日：{v.lastPlannedDate ? dayjs(v.lastPlannedDate).format('YYYY/MM/DD') : '-'}</div>
                                </div>
                            </button>
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