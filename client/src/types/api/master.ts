import { ICourseType } from './course';
import { IIngredientUnit, IRecipeCategory, ISeasoningUnit } from './recipe';
import { IShoppingCategory, IShoppingTag } from './shopping';

export interface IGetMasterResponse {
    recipeCategories: IRecipeCategory[];
    ingredientUnits: IIngredientUnit[];
    seasoningUnits: ISeasoningUnit[];
    courseTypes: ICourseType[];
    shoppingCategories: IShoppingCategory[];
    shoppingTags: IShoppingTag[];
}
