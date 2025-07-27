export interface IGetRecipesResponse {
    data: IRecipe[];
    total: number;
}
export type IGetRecipeResponse = IRecipe;

export interface IPostRecipeRequest {
    name: string;
    url: string | null;
    instructions: string | null;
    memo: string | null;
    categoryIds: string[];
    seasonings: ISeasoning[];
    ingredients: IIngredient[];
    thumbnail: File | null;
}

export type IPutRecipeRequest = IPostRecipeRequest & { id: string };

export interface IPostRecipeCategoryRequest {
    name: string;
    order: number;
}

export interface IRecipeCategory {
    id: string;
    name?: string; // nameは省略可（idだけで十分な場合もある）
    order?: number;
}

export interface ISeasoningUnit {
    id: string;
    name: string;
    order: number;
}

export interface ISeasoning {
    id?: string; // 新規作成時はidなしも許容
    name: string;
    quantity: number | null;
    unitId: string;
    order?: number;
}

export interface IIngredientUnit {
    id: string;
    name: string;
    order: number;
}

export interface IIngredient {
    id?: string; // 新規作成時はidなしも許容
    name: string;
    quantity: number | null;
    unitId: string;
    order?: number;
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
    instructions: string;
    memo: string;
    categoryIds: string[];
    seasonings: ISeasoning[];
    ingredients: IIngredient[];
}
