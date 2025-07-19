export interface IGetRecipesResponse {
    data: IRecipe[];
    total: number;
}

export interface IRecipe {
    id: string;
    name: string;
    thumbnail: {
        url: string;
        width: number;
        height: number;
    };
    url: string;
    recipe: string;
    memo: string;
    categories: IRecipeCategory[];
    seasonings: IRecipeSeasoning[];
    ingredients: IRecipeIngredient[];
}

export interface IRecipeCategory {
    id: string;
    name: string;
}

export interface IRecipeSeasoning {
    id: string;
    name: string;
    quantity: number;
    unitId: string;
}

export interface IRecipeIngredient {
    id: string;
    name: string;
    quantity: number;
    unitId: string;
}
