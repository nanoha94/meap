import { Loading } from '@/components/common';
import RecipeEditPage from '@/pages/recipe/edit/RecipeEditPage';
import { Suspense } from 'react';

const Page = () => {
    return (
        <Suspense fallback={<Loading />}>
            <RecipeEditPage />
        </Suspense>
    );
};

export default Page;
