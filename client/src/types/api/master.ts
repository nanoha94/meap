import { IBaseApiResponse , IMealCategory, IIngredientCategory, IIngredientUnit,IRecipeCategory,IShoppingCategory, IShoppingTag,IUser } from '../api';

export type IGetMasterResponse = IBaseApiResponse<IMaster>;

export interface IMaster {
    users: IUser[];
    recipeCategories: IRecipeCategory[];
    ingredientCategories: IIngredientCategory[];
    ingredientUnits: IIngredientUnit[];
    courseTypes: ICourseType[];
    shoppingCategories: IShoppingCategory[];
    shoppingTags: IShoppingTag[];
}

