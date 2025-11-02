import { IImage } from './image';
import { IIngredient } from './ingredient';

export interface IGetRecipesResponse {
    data: IRecipe[];
    total: number;
}

export interface IPostRecipeRequest {
    name: string;
    url: string | null;
    instructions: string | null;
    memo: string | null;
    categoryIds: string[];
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
    order: number;
}

export interface IRecipeStep {
    id: string;
    instruction: string;
    image: IImage | null;
    order: number;
}

export interface IRecipe {
    id: string;
    name: string;
    url: string;
    memo: string;
    thumbnail: IImage | null;
    categories: IRecipeCategory[];
    ingredients: IIngredient[];
    steps: IRecipeStep[];
}
