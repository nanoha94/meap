import { Header } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { TIMEOUT_MS } from '@/constants';
// import { apiClient } from '@/lib/apiClient';
// import { IGetRecipesResponse } from '@/types/api/recipe';
import { Suspense } from 'react';
import Loading from '../../loading';
import { HeaderRecipeDeleteButton } from '@/models/recipe/components';
import { Pencil } from 'lucide-react';
import { HeaderLinkTextButton } from '@/components/common/HeaderTextButtons';

interface Props {
    params: Promise<{ id: string }>;
}

interface RecipePageWithDataProps {
    id: string;
}
const RecipePageWithData = async ({ id }: RecipePageWithDataProps) => {
    console.log(id);
    // let recipes: IGetRecipesResponse = { data: [], total: 0 };
    let errorMessage: string = '';

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        // recipes = await apiClient('/recipes', {
        //     signal: controller.signal,
        // });
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
            <Header title="料理/レシピ">
                <div className="flex items-center gap-x-4">
                    <HeaderRecipeDeleteButton />
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
                詳細
            </main>
        </>
    );
};

const Page = async ({ params }: Props) => {
    const { id } = await params;
    return (
        <Suspense fallback={<Loading />}>
            <RecipePageWithData id={id} />
        </Suspense>
    );
};

export default Page;
