import { ICourseType } from './course';
import { IRecipeCategory } from './recipe';
import { IShoppingCategory, IShoppingTag } from './shopping';

export interface IGetMasterResponse {
    recipeCategories: IRecipeCategory[];
    courseTypes: ICourseType[];
    shoppingCategories: IShoppingCategory[];
    shoppingTags: IShoppingTag[];
}
