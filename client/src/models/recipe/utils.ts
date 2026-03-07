import { RECIPES_PER_PAGE } from "./constants";
import { RecipeFilterFormData } from "./types";

type GetQueryStringOptions = { forApi?: boolean };

/**
 * クエリ文字列を生成する
 * @param sortOptions 並び替えオプション（ソート、並び順）
 * @param filterOptions フィルターオプション
 * @param options forApi: true のとき category_ids を配列形式（category_ids[]）で出力（Laravel API用）
 * @returns URLSearchParams
 */
const getQueryString = (
    sortOptions: { sort: string, order: string },
    filterOptions: RecipeFilterFormData,
    options: GetQueryStringOptions = {},
) => {
    const query = new URLSearchParams();
    query.set('sort', sortOptions.sort);
    query.set('order', sortOptions.order);
    if (filterOptions.recipeName?.trim()) query.set('recipe_name', filterOptions.recipeName.trim());
    if (filterOptions.ingredientName?.trim()) query.set('ingredient_name', filterOptions.ingredientName.trim());
    if (filterOptions.categoryIds?.length) {
        if (options.forApi) {
            for (const id of filterOptions.categoryIds) query.append('category_ids[]', id);
        } else {
            query.set('category_ids', filterOptions.categoryIds.join(','));
        }
    }
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
    const query = getQueryString(sortOptions, filterOptions, { forApi: true });

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

