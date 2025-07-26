import { Header } from '@/components/common';
import RecipeEditPage from '@/pages/recipe/RecipeEditPage';

const Page = () => {
    return (
        <>
            <Header title="料理/レシピ追加" />
            <main>
                <RecipeEditPage />
            </main>
        </>
    );
};

export default Page;
