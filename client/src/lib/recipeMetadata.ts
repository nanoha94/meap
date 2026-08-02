import type { Metadata } from 'next';

import { METADATA } from '@/constants/metadata';
import { fetchData } from '@/lib/apiClient';
import { IGetRecipeShowResponse } from '@/types';

const fetchRecipeName = async (id: string) => {
    const { data: recipe } = await fetchData<IGetRecipeShowResponse>(
        `/recipes/${id}`,
        { suppressNotFoundLog: true },
    );

    return recipe?.data?.name;
};

export const createRecipeDetailMetadata = async (
    id: string,
): Promise<Metadata> => {
    const name = await fetchRecipeName(id);

    return { title: name ?? METADATA.PAGE.RECIPE_DETAIL };
};

export const createRecipeEditMetadata = async (id: string): Promise<Metadata> => {
    const name = await fetchRecipeName(id);

    return {
        title: name ? `${name}の編集` : METADATA.PAGE.RECIPE_EDIT,
    };
};
