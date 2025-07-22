import { Header } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { timeout_ms } from '@/constants';
import { apiClient } from '@/lib/apiClient';
import RecipePage from '@/pages/recipe/RecipePage';
import { IGetRecipesResponse } from '@/types/api/recipe';
import { Suspense } from 'react';
import Loading from '../loading';
import { CirclePlus } from 'lucide-react';
import { HeaderLinkTextButton } from '@/components/common/HeaderTextButtons';

const RecipePageWithData = async () => {
    let recipes: IGetRecipesResponse = { data: [], total: 0 };
    let errorMessage: string = '';

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout_ms);

        recipes = await apiClient('/recipes', {
            signal: controller.signal,
        });
        clearTimeout(timeoutId);
    } catch (error) {
        console.error(error);
        // エラーオブジェクトから安全に文字列を抽出
        if (error instanceof Error && error.name === 'AbortError') {
            errorMessage =
                'リクエストがタイムアウトしました。再度お試しください。';
        } else {
            errorMessage =
                error instanceof Error
                    ? error.message
                    : typeof error === 'string'
                      ? error
                      : 'データの取得に失敗しました';
        }
    }
    return (
        <>
            <Header title="料理/レシピ一覧">
                <div className="gap-x-4 hidden md:flex">
                    <HeaderLinkTextButton
                        href="/recipe/new"
                        colorVariant="secondary">
                        <CirclePlus size={20} />
                        料理/レシピを追加
                    </HeaderLinkTextButton>
                </div>
            </Header>
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                <RecipePage fetchRecipes={recipes['data']} />
            </main>
        </>
    );
};

const Page = () => {
    return (
        <Suspense fallback={<Loading />}>
            <RecipePageWithData />
        </Suspense>
    );
};

export default Page;
