import React from 'react';
import { Suspense } from 'react';
import RecipeEditPage from '@/pages/recipe/edit/RecipeEditPage';

import { Loading } from '@/components';

const Page = () => {
    return (
        <Suspense fallback={<Loading />}>
            <RecipeEditPage />
        </Suspense>
    );
};

export default Page;
