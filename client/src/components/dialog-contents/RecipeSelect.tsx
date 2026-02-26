"use client";
import React from "react";
import { CirclePlus, SlidersHorizontal } from "lucide-react";
import Image from "next/image";
import { useRouter } from "next/navigation";

import { RecipeFilterFormData, sortOptions, useRecipeApi } from "@/models/recipe";
import { IRecipe, IRecipeListItem } from "@/types";
import { BUTTON_TYPE, colors } from "@/constants";
import { RecipeFilterForm, TextButton } from "@/components";
import { useDialog } from "@/hooks";
import { StyledSelect } from "../form-fields";
import dayjs from "dayjs";
import Pagination from "../Pagination";

interface Props {
    initFetchedRecipes?: {
        recipes: IRecipe[];
        pageSize: number;
        currentPage: number;
    };
    selectedRecipe: IRecipeListItem | null;
    disabledRecipes: string[];
    onSelectedRecipeChange: (recipe: IRecipeListItem) => void;
    onConfirm: () => void;
}

const RecipeSelect = ({ initFetchedRecipes, selectedRecipe, disabledRecipes, onSelectedRecipeChange, onConfirm }: Props) => {
    const router = useRouter();
    const { openDialog, closeDialog } = useDialog();
    const { fetchRecipes } = useRecipeApi();
    const [sortOptionId, setSortOptionId] = React.useState<string>(sortOptions[0].id);
    const [filterOptions, setFilterOptions] = React.useState<RecipeFilterFormData>({ recipeName: '', ingredientName: '', categoryId: '', lastPlannedDateFrom: '', lastPlannedDateTo: '' });
    const [recipes, setRecipes] = React.useState<IRecipe[]>(initFetchedRecipes?.recipes ?? []);
    const [pageSize, setPageSize] = React.useState<number>(initFetchedRecipes?.pageSize ?? 0);
    const [currentPage, setCurrentPage] = React.useState<number>(initFetchedRecipes?.currentPage ?? 1);

    /**
     * 並び替え処理
     * @param e SelectChangeEvent
     */
    const handleChangeSortOption = async (e: React.ChangeEvent<HTMLSelectElement>) => {
        const selected = e.target.value;
        setSortOptionId(selected);
        searchRecipes(selected, filterOptions, currentPage);
    };

    /**
     * 絞り込み条件変更処理
     * @param filterOptions 絞り込み条件
     */
    const handleChangeFilterOptions = async (filterOptions: RecipeFilterFormData) => {
        setFilterOptions(filterOptions);
        searchRecipes(sortOptionId, filterOptions, currentPage);
    };

    /**
     * ページ番号変更処理
     * @param page ページ番号
     */
    const handleChangePage = async (page: number) => {
        searchRecipes(sortOptionId, filterOptions, page);
    };

    const searchRecipes = async (sortOptionId?: string, filterOptions?: RecipeFilterFormData, page?: number) => {
        const result = await fetchRecipes(sortOptionId, filterOptions, page);
        if (result) {
            setRecipes(result.recipes);
            setPageSize(result.pageSize);
            setCurrentPage(result.currentPage);
        }
    };


    return (
        <div className="flex flex-col gap-y-5">
            <div className="flex justify-between gap-x-5 gap-y-2 flex-wrap">
                <button type="button" onClick={() => {
                    openDialog({
                        title: '絞り込み条件',
                        children: () => (
                            <RecipeFilterForm search={handleChangeFilterOptions} />
                        ),
                    });
                }} className="py-1 px-2 flex items-center gap-x-2 rounded hover:bg-gray-light">
                    <SlidersHorizontal color={colors.black} strokeWidth={1.5} />絞り込み</button>
                <StyledSelect value={sortOptionId} options={sortOptions} onChange={handleChangeSortOption} isShowPlaceholder={false} className="!w-auto" />
            </div>
            <div className="flex flex-col gap-y-14">
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
                <Pagination pageSize={pageSize} currentPage={currentPage} onPageChange={(page) => handleChangePage(page)} />
            </div>
        </div>
    );
};

export default RecipeSelect;