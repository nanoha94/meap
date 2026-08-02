import { IBaseApiResponseWithData, IMealCategory, IIngredientUnit, IRecipeCategory, IShoppingCategory, IShoppingTag, IUser } from '../api';

export type IGetMasterResponse = IBaseApiResponseWithData<IMaster>;

export interface IMaster {
    users: IUser[];
    recipeCategories: IRecipeCategory[];
    ingredientUnits: IIngredientUnit[];
    mealCategories: IMealCategory[];
    shoppingCategories: IShoppingCategory[];
    shoppingTags: IShoppingTag[];
}

