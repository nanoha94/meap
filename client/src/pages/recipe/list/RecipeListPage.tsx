'use client';
import { RecipeList } from '@/models/recipe/components';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { useGlobalStore } from '@/stores';
import { IRecipe } from '@/types/api';
import { useSnackbars } from '@/hooks/useSnackbars';
import React from 'react';
import { Header, HeaderTextButton } from '@/components/common';
import { CirclePlus } from 'lucide-react';

interface Props {
    fetchRecipes: IRecipe[];
    total: number;
    errorMessage?: string;
}

const RecipeListPage = ({
    fetchRecipes = [],
    total = 0,
    errorMessage,
}: Props) => {
    const { setRecipes: setStoreRecipes, isLoadings } = useRecipeStore();
    const { setIsLoading } = useGlobalStore();
    const { addSnackbar } = useSnackbars();

    React.useEffect(() => {
        setIsLoading(isLoadings.recipe || isLoadings.recipeCategory);
    }, [isLoadings]);

    React.useEffect(() => {
        if (fetchRecipes) {
            setStoreRecipes(fetchRecipes);
        }
    }, [fetchRecipes]);

    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage]);

    return (
        <>
            <Header
                title="料理/レシピ一覧"
                rightContent={
                    <div className="hidden md:flex">
                        <HeaderTextButton
                            href="/recipe/new"
                            colorVariant="secondary">
                            <CirclePlus size={20} />
                            料理/レシピを追加
                        </HeaderTextButton>
                    </div>
                }
            />
            <main>
                <div className="p-5 pb-[60px] md:px-10">
                    {total > 0 ? (
                        <RecipeList />
                    ) : (
                        <p>まだ料理/レシピが登録されていません。</p>
                    )}
                </div>
            </main>
        </>
    );
};

export default RecipeListPage;
