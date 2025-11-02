import { ICourseType } from './course';
import { IIngredientUnit } from './ingredient';
import { IRecipeCategory } from './recipe';
import { IShoppingTag } from './shopping';

export interface IGetMasterResponse {
    data: {
        recipeCategories: IRecipeCategory[];
        ingredientUnits: IIngredientUnit[];
        courseTypes: ICourseType[];
        shoppingTags: IShoppingTag[];
    };
}
