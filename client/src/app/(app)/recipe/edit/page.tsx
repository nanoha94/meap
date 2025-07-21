import { Header } from '@/components/common';
import RecipeEditPage from '@/pages/recipe/RecipeEditPage';

const Page = () => {
    return (
        <>
            <Header title="料理/レシピ登録" />
            <main>
                <RecipeEditPage />
            </main>
        </>
    );
};

export default Page;
