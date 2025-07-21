export interface IGetRecipesResponse {
    data: IRecipe[];
    total: number;
}
export interface IPostRecipeRequest {
    name: string;
    url?: string;
    recipe?: string;
    memo?: string;
    categories?: IRecipeCategory[];
    seasonings?: ISeasoning[];
    ingredients?: IIngredient[];
    thumbnailImage?: File;
}

export type IPutRecipeRequest = IPostRecipeRequest & { id: string };

export interface IRecipeCategory {
    id: string;
    name?: string; // nameは省略可（idだけで十分な場合もある）
}

export interface ISeasoningUnit {
    id: string;
    name: string;
    order: number;
}

export interface ISeasoning {
    id?: string; // 新規作成時はidなしも許容
    name: string;
    quantity?: number;
    unitId: string;
}

export interface IIngredientUnit {
    id: string;
    name: string;
    order: number;
}

export interface IIngredient {
    id?: string; // 新規作成時はidなしも許容
    name: string;
    quantity?: number;
    unitId: string;
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
    categories: IRecipeCategory[];
    seasonings: ISeasoning[];
    ingredients: IIngredient[];
}
