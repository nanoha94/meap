'use client';

import React from 'react';
import Image from 'next/image';
import { useRouter } from 'next/navigation';
import { ChevronRight, SlidersHorizontal } from 'lucide-react';

import { Button, RecipeFilterForm, TextButton } from '@/components';
import {
    BUTTON_TYPE,
    BUTTON_VARIANT,
    COLOR_VARIANT,
    LINK_TO,
    colors,
} from '@/constants';
import { useDialog } from '@/hooks';
import { RecipeFilterFormData, sortOptions, useRecipeApi } from '@/models/recipe';
import { IRecipe, IRecipeListItem } from '@/types';
import { formatDisplayDate } from '@/utils';

import Pagination from '../Pagination';
import { StyledSelect } from '../form-fields';

interface Props {
    initFetchedRecipes?: {
        recipes: IRecipe[];
        pageSize: number;
        currentPage: number;
    };
    defaultItems: IRecipeListItem[];
    onSave: (selectedItems: IRecipeListItem[]) => void;
}

const toRecipeListItem = (recipe: IRecipe): IRecipeListItem => ({
    id: recipe.id,
    name: recipe.name,
    categories: recipe.categories,
    thumbnail: recipe.thumbnail,
    lastPlannedDate: recipe.lastPlannedDate,
});

const RecipeSelect = ({ initFetchedRecipes, defaultItems, onSave }: Props) => {
    const router = useRouter();
    const { openDialog, closeDialog, updateCurrentDialogConfig } = useDialog();
    const { fetchRecipes } = useRecipeApi();
    const [sortOptionId, setSortOptionId] = React.useState<string>(sortOptions[0].id);
    const [filterOptions, setFilterOptions] = React.useState<RecipeFilterFormData>({ recipeName: '', ingredientName: '', categoryIds: [], lastPlannedDateFrom: '', lastPlannedDateTo: '' });
    const [recipes, setRecipes] = React.useState<IRecipe[]>(initFetchedRecipes?.recipes ?? []);
    const [pageSize, setPageSize] = React.useState<number>(initFetchedRecipes?.pageSize ?? 0);
    const [currentPage, setCurrentPage] = React.useState<number>(initFetchedRecipes?.currentPage ?? 1);
    const [selectedItems, setSelectedItems] = React.useState<IRecipeListItem[]>(defaultItems);

    const selectedIdSet = React.useMemo(
        () => new Set(selectedItems.map((r) => r.id)),
        [selectedItems],
    );

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

    const toggleRecipeSelection = (recipe: IRecipe) => {
        const listItem = toRecipeListItem(recipe);
        if (selectedIdSet.has(recipe.id)) {
            const next = selectedItems.filter((r) => r.id !== recipe.id);
            setSelectedItems(next);
        } else {
            const next = [...selectedItems, listItem];
            setSelectedItems(next);
        }
    };

    /**
     * 保存ボタンの無効化判定（ダイアログを開いた時点の選択と同じなら無効）
     */
    const isDisabledSaveButton = React.useMemo(
        () => {
            if (selectedItems.length !== defaultItems.length) return false;
            const sortIds = (items: IRecipeListItem[]) => [...items].map((x) => x.id).sort().join('\0');
            return sortIds(selectedItems) === sortIds(defaultItems);
        },
        [defaultItems, selectedItems],
    );

    /**
     * 保存ボタン押下時の処理
     */
    const handleSave = React.useCallback(() => {
        if (isDisabledSaveButton) return;
        onSave(selectedItems);
    }, [isDisabledSaveButton, onSave, selectedItems]);

    /**
     * フッターを更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({
            footer: <FormFooter
                selectedItems={selectedItems}
                isDisabledSaveButton={isDisabledSaveButton}
                closeDialog={closeDialog}
                handleSave={handleSave}
            />,
            isCheckBeforeClose: !isDisabledSaveButton,
        });
    }, [selectedItems, isDisabledSaveButton, updateCurrentDialogConfig, closeDialog, handleSave]);

    return (
        <div className="flex flex-col gap-y-5">
            <div className="flex justify-between gap-x-5 gap-y-2 flex-wrap">
                <button type="button" onClick={() => {
                    openDialog({
                        title: '絞り込み条件',
                        children: <RecipeFilterForm
                            search={handleChangeFilterOptions}
                            defaultValues={filterOptions}
                            suppressNavigationGuard
                        />,
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
                                    className={`relative w-full text-left flex flex-col bg-white rounded-md overflow-hidden transition-colors cursor-pointer hover:bg-gray-light border-2 ${selectedIdSet.has(v.id) ? 'border-primary-main' : 'border-transparent'}`}
                                    style={{ boxShadow: '1px 1px 5px rgba(0, 0, 0, 15%)' }}
                                    onClick={() => toggleRecipeSelection(v)}
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
                                        <div className="text-xs">前回の献立日：{v.lastPlannedDate ? formatDisplayDate(v.lastPlannedDate) : '-'}</div>
                                    </div>
                                </button>
                            ))}
                        </div>
                        <TextButton
                            type={BUTTON_TYPE.BUTTON}
                            colorVariant={COLOR_VARIANT.SECONDARY}
                            onClick={() => { router.push(LINK_TO.RECIPE.NEW); closeDialog(false); }}>
                            料理/レシピを追加
                            <ChevronRight size={20} />
                        </TextButton>
                    </>
                ) : (
                    <div className="flex flex-col gap-y-2">
                        <p>まだ料理/レシピが登録されていません。</p>
                        <TextButton
                            type={BUTTON_TYPE.BUTTON}
                            colorVariant={COLOR_VARIANT.SECONDARY}
                            onClick={() => { router.push(LINK_TO.RECIPE.NEW); closeDialog(false); }}>
                            料理/レシピを追加
                            <ChevronRight size={20} />
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

interface FormFooterProps {
    selectedItems: IRecipeListItem[];
    isDisabledSaveButton: boolean;
    closeDialog: () => void;
    handleSave: () => void;
}

const FormFooter = ({ selectedItems, isDisabledSaveButton, closeDialog, handleSave }: FormFooterProps) => {
    return <div className="p-3 w-full flex flex-wrap gap-4 items-end bg-white rounded-b-xl" style={{ boxShadow: '0px -5px 8px 0 rgba(0, 0, 0, 10%)' }}>
        <div className=" w-full h-fit flex flex-col sm:flex-row gap-6 sm:items-end justify-between">
            <div className="flex flex-col gap-y-2 flex-1">
                <div className="text-lg">選択中の料理</div>
                <div className="flex gap-x-2">
                    {selectedItems.length > 0 ? selectedItems.map((item, index) => (
                        <React.Fragment key={item.id}>
                            <div>{item.name}</div>
                            {index < selectedItems.length - 1 && <div>/</div>}
                        </React.Fragment>
                    )) : <div>まだ選択されていません</div>}
                </div>
            </div>
            <div className="mx-auto sm:mr-0 sm:max-w-[320px] w-full flex gap-x-6">
                <Button
                    type={BUTTON_TYPE.BUTTON}
                    colorVariant={COLOR_VARIANT.GRAY}
                    variant={BUTTON_VARIANT.OUTLINED}
                    onClick={() => closeDialog()}>
                    戻る
                </Button>
                <Button type={BUTTON_TYPE.BUTTON}
                    disabled={isDisabledSaveButton}
                    onClick={handleSave}
                >
                    保存
                </Button>
            </div>
        </div>
    </div>;
};
