import React from 'react';
import { Suspense } from 'react';
import { redirect } from 'next/navigation';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { RECIPES_PER_PAGE, sortOptions } from '@/models/recipe';
import { RecipeFilterFormData } from '@/models/recipe/types';
import { getApiQueryString, getBrowserQueryString } from '@/models/recipe/utils';
import RecipeListPage from '@/pages/recipe/list/RecipeListPage';
import { IGetRecipeIndexResponse } from '@/types';

interface RecipePageSearchParams {
    sort?: string;
    order?: string;
    recipeName?: string;
    ingredientName?: string;
    categoryIds?: string;
    lastPlannedDateFrom?: string;
    lastPlannedDateTo?: string;
    page?: number;
}

interface Props {
    searchParams: Promise<{
        sort?: string;
        order?: string;
        recipe_name?: string;
        ingredient_name?: string;
        category_ids?: string;
        last_planned_date_from?: string;
        last_planned_date_to?: string;
        page?: number;
    }>;
}

const defaultSort = sortOptions[0];

const RecipePageWithData = async ({
    sort,
    order,
    recipeName,
    ingredientName,
    categoryIds,
    lastPlannedDateFrom,
    lastPlannedDateTo,
    page,
}: RecipePageSearchParams) => {
    const path = `/recipes?${getApiQueryString(
        {
            sort: sort ?? defaultSort.sort,
            order: order ?? defaultSort.order
        },
        {
            recipeName: recipeName?.trim(),
            ingredientName: ingredientName?.trim(),
            categoryIds: categoryIds?.split(',').map(id => id.trim()),
            lastPlannedDateFrom: lastPlannedDateFrom?.trim(),
            lastPlannedDateTo: lastPlannedDateTo?.trim()
        }, page ?? 1)}`;

    const { data: recipes, errorMessage } = await fetchData<IGetRecipeIndexResponse>(path);

    const sortOptionId = sort && order
        ? sortOptions.find(
            o => o.sort === sort && o.order === order,
        )?.id ?? defaultSort.id
        : defaultSort.id;

    const filterOptions: RecipeFilterFormData = {
        recipeName: recipeName ?? '',
        ingredientName: ingredientName ?? '',
        categoryIds: categoryIds?.split(',').map(id => id.trim()) ?? [],
        lastPlannedDateFrom: lastPlannedDateFrom ?? '',
        lastPlannedDateTo: lastPlannedDateTo ?? '',
    };

    return (
        <RecipeListPage
            fetchedRecipes={recipes?.data ?? []}
            pageSize={Math.ceil((recipes?.total ?? 0) / RECIPES_PER_PAGE)}
            currentPage={Number(page ?? 1)}
            errorMessage={errorMessage}
            sortOptionId={sortOptionId}
            filterOptions={filterOptions}
        />
    );
};

const Page = async ({ searchParams }: Props) => {
    const resolved = await Promise.resolve(searchParams);
    const { sort, order, recipe_name, ingredient_name, category_ids, last_planned_date_from, last_planned_date_to, page } = resolved;

    const filterOptions: RecipeFilterFormData = {
        recipeName: recipe_name ?? '',
        ingredientName: ingredient_name ?? '',
        categoryIds: category_ids?.split(',').map(id => id.trim()) ?? [],
        lastPlannedDateFrom: last_planned_date_from ?? '',
        lastPlannedDateTo: last_planned_date_to ?? '',
    };

    const sortOptions = {
        sort: sort ?? defaultSort.sort,
        order: order ?? defaultSort.order,
    };

    if (!sort || !order || !page) {
        redirect(`/recipe?${getBrowserQueryString(sortOptions, filterOptions, page ?? 1)}`);
    }
    if (page < 1) {
        redirect(`/recipe?${getBrowserQueryString(sortOptions, filterOptions, 1)}`);
    }

    return (
        <Suspense fallback={<Loading />}>
            <RecipePageWithData
                sort={sort}
                order={order}
                recipeName={recipe_name}
                ingredientName={ingredient_name}
                categoryIds={category_ids}
                lastPlannedDateFrom={last_planned_date_from}
                lastPlannedDateTo={last_planned_date_to}
                page={page}
            />
        </Suspense>
    );
};

export default Page;
