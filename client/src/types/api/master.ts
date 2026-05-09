import { IBaseApiResponseWithData, IMealCategory, IIngredientCategory, IIngredientUnit,IRecipeCategory,IShoppingCategory, IShoppingTag,IUser } from '../api';

export type IGetMasterResponse = IBaseApiResponseWithData<IMaster>;

export interface IMaster {
    users: IUser[];
    recipeCategories: IRecipeCategory[];
    ingredientCategories: IIngredientCategory[];
    ingredientUnits: IIngredientUnit[];
    mealCategories: IMealCategory[];
    shoppingCategories: IShoppingCategory[];
    shoppingTags: IShoppingTag[];
}

