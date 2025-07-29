import { Header } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { TIMEOUT_MS } from '@/constants';
import { Suspense } from 'react';
import Loading from '../../loading';
import { HeaderRecipeDeleteButton } from '@/models/recipe/components';
import { Pencil } from 'lucide-react';
import { HeaderLinkTextButton } from '@/components/common/HeaderTextButtons';
import { apiClient } from '@/lib/apiClient';
import { IGetRecipeResponse } from '@/types/api/recipe';
import RecipeDetailPage from '@/pages/recipe/RecipeDetailPage';
import { notFound } from 'next/navigation';

interface Props {
    params: Promise<{ id: string }>;
}

interface PageWithDataProps {
    id: string;
}
const PageWithData = async ({ id }: PageWithDataProps) => {
    let recipe: IGetRecipeResponse = {} as IGetRecipeResponse;
    let errorMessage: string = '';

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        recipe = await apiClient(`/recipes/${id}`, {
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
        notFound();
    }

    return (
        <>
            <Header title="料理/レシピ">
                <div className="flex items-center gap-x-4">
                    <HeaderRecipeDeleteButton id={id} name={recipe.name} />
                    <HeaderLinkTextButton
                        href={`/recipe/${id}/edit`}
                        colorVariant="secondary">
                        <Pencil size={20} />
                        編集
                    </HeaderLinkTextButton>
                </div>
            </Header>
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                <RecipeDetailPage recipe={recipe} />
            </main>
        </>
    );
};

const Page = async ({ params }: Props) => {
    const { id } = await params;
    return (
        <Suspense fallback={<Loading />}>
            <PageWithData id={id} />
        </Suspense>
    );
};

export default Page;
