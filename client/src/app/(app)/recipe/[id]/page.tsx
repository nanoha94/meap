import { Header } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { timeout_ms } from '@/constants';
// import { apiClient } from '@/lib/apiClient';
// import { IGetRecipesResponse } from '@/types/api/recipe';
import { Suspense } from 'react';
import Loading from '../../loading';

interface Props {
    params: Promise<{ id: string }>;
}

const RecipePageWithData = async ({ params }: Props) => {
    const { id } = await params;
    console.log(id);
    // let recipes: IGetRecipesResponse = { data: [], total: 0 };
    let errorMessage: string = '';

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout_ms);

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
            <Header title="料理/レシピ一覧" />
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                詳細
            </main>
        </>
    );
};

const Page = ({ params }: { params: Promise<{ id: string }> }) => {
    return (
        <Suspense fallback={<Loading />}>
            <RecipePageWithData params={params} />
        </Suspense>
    );
};

export default Page;
