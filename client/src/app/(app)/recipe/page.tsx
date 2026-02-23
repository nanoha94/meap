import React from 'react';
import { Suspense } from 'react';
import RecipeListPage from '@/pages/recipe/list/RecipeListPage';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { sortOptions } from '@/models/recipe';
import { RecipeFilterFormData } from '@/models/recipe/types';
import { IGetRecipeIndexResponse } from '@/types';
import { redirect } from 'next/navigation';
import { getQueryString } from '@/models/recipe/utils';

interface RecipePageSearchParams {
    sort?: string;
    order?: string;
    recipeName?: string;
    ingredientName?: string;
    categoryId?: string;
    lastPlannedDateFrom?: string;
    lastPlannedDateTo?: string;
}

interface Props {
    searchParams: Promise<{
        sort?: string;
        order?: string;
        recipe_name?: string;
        ingredient_name?: string;
        category_id?: string;
        last_planned_date_from?: string;
        last_planned_date_to?: string;
    }>;
}

const defaultSort = sortOptions[0];

const RecipePageWithData = async ({
    sort,
    order,
    recipeName,
    ingredientName,
    categoryId,
    lastPlannedDateFrom,
    lastPlannedDateTo,
}: RecipePageSearchParams) => {
    const path = `/recipes?${getQueryString(
        {
            sort: sort ?? defaultSort.sort,
            order: order ?? defaultSort.order
        },
        {
            recipeName: recipeName?.trim(),
            ingredientName: ingredientName?.trim(),
            categoryId: categoryId?.trim(),
            lastPlannedDateFrom: lastPlannedDateFrom?.trim(),
            lastPlannedDateTo: lastPlannedDateTo?.trim()
        })}`;

    const { data: recipes, errorMessage } = await fetchData<IGetRecipeIndexResponse>(path);

    const sortOptionId = sort && order
        ? sortOptions.find(
            o => o.sort === sort && o.order === order,
        )?.id ?? defaultSort.id
        : defaultSort.id;

    const filterOptions: RecipeFilterFormData = {
        recipeName: recipeName ?? '',
        ingredientName: ingredientName ?? '',
        categoryId: categoryId ?? '',
        lastPlannedDateFrom: lastPlannedDateFrom ?? '',
        lastPlannedDateTo: lastPlannedDateTo ?? '',
    };

    return (
        <RecipeListPage
            fetchedRecipes={recipes?.data ?? []}
            fetchedRecipesTotal={recipes?.total ?? 0}
            errorMessage={errorMessage}
            sortOptionId={sortOptionId}
            filterOptions={filterOptions}
        />
    );
};

const Page = async ({ searchParams }: Props) => {
    const resolved = await Promise.resolve(searchParams);
    const { sort, order, recipe_name, ingredient_name, category_id, last_planned_date_from, last_planned_date_to } = resolved;
    if (!sort && !order) {
        redirect(`/recipe?sort=${defaultSort.sort}&order=${defaultSort.order}`);
    }

    return (
        <Suspense fallback={<Loading />}>
            <RecipePageWithData
                sort={sort}
                order={order}
                recipeName={recipe_name}
                ingredientName={ingredient_name}
                categoryId={category_id}
                lastPlannedDateFrom={last_planned_date_from}
                lastPlannedDateTo={last_planned_date_to}
            />
        </Suspense>
    );
};

export default Page;
