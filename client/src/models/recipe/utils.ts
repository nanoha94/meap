import { RecipeFilterFormData } from "./types";

export const getQueryString = (sortOptions: { sort: string, order: string }, filterOptions: RecipeFilterFormData) => {
    const query = new URLSearchParams();
    query.set('sort', sortOptions.sort);
    query.set('order', sortOptions.order);
    if (filterOptions.recipeName?.trim()) query.set('recipe_name', filterOptions.recipeName.trim());
    if (filterOptions.ingredientName?.trim()) query.set('ingredient_name', filterOptions.ingredientName.trim());
    if (filterOptions.categoryId?.trim()) query.set('category_id', filterOptions.categoryId.trim());
    if (filterOptions.lastPlannedDateFrom?.trim()) query.set('last_planned_date_from', filterOptions.lastPlannedDateFrom.trim());
    if (filterOptions.lastPlannedDateTo?.trim()) query.set('last_planned_date_to', filterOptions.lastPlannedDateTo.trim());
    return query.toString();
};

