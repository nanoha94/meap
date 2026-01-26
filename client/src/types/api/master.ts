import { IBaseApiResponse } from './common';
import { ICourseType } from './course';
import { IIngredientCategory, IIngredientUnit } from './ingredient';
import { IRecipeCategory } from './recipe';
import { IShoppingCategory, IShoppingTag } from './shopping';
import { IUser } from './user';

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

