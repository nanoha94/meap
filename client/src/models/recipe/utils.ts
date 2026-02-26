import { RECIPES_PER_PAGE } from "./constants";
import { RecipeFilterFormData } from "./types";

/**
 * クエリ文字列を生成する
 * @param sortOptions 並び替えオプション（ソート、並び順）
 * @param filterOptions フィルターオプション
 * @returns URLSearchParams
 */
const getQueryString = (sortOptions: { sort: string, order: string }, filterOptions: RecipeFilterFormData) => {
    const query = new URLSearchParams();
    query.set('sort', sortOptions.sort);
    query.set('order', sortOptions.order);
    if (filterOptions.recipeName?.trim()) query.set('recipe_name', filterOptions.recipeName.trim());
    if (filterOptions.ingredientName?.trim()) query.set('ingredient_name', filterOptions.ingredientName.trim());
    if (filterOptions.categoryId?.trim()) query.set('category_ids', filterOptions.categoryId.trim());
    if (filterOptions.lastPlannedDateFrom?.trim()) query.set('last_planned_date_from', filterOptions.lastPlannedDateFrom.trim());
    if (filterOptions.lastPlannedDateTo?.trim()) query.set('last_planned_date_to', filterOptions.lastPlannedDateTo.trim());
    return query;
};

/**
 * APIリクエスト用のクエリ文字列を生成する
 * @param sortOptions 並び替えオプション（ソート、並び順）
 * @param filterOptions フィルターオプション
 * @param page ページ番号
 * @returns クエリ文字列
 */
export const getApiQueryString = (sortOptions: { sort: string, order: string }, filterOptions: RecipeFilterFormData, page?: number) => {
    const query = getQueryString(sortOptions, filterOptions);

    if (page) {
        query.set('limit', RECIPES_PER_PAGE.toString());
        query.set('offset', ((page - 1) * RECIPES_PER_PAGE).toString());
    }
    return query.toString();
};

/**
 * ブラウザURL用のクエリ文字列を生成する
 * @param sortOptions 並び替えオプション（ソート、並び順）
 * @param filterOptions フィルターオプション
 * @param page ページ番号
 * @returns クエリ文字列
 */
export const getBrowserQueryString = (sortOptions: { sort: string, order: string }, filterOptions: RecipeFilterFormData, page?: number) => {
    const query = getQueryString(sortOptions, filterOptions);
    if (page) {
        query.set('page', page.toString());
    }
    return query.toString();
};

