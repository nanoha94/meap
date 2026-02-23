import { useForm } from "react-hook-form";
import { RecipeFilterFormData } from "../types";
import { useRecipeStore } from "./useRecipeStores";
import { usePathname, useRouter } from "next/navigation";
import { useGlobalStore } from "@/stores";
import { getQueryString } from "../utils";
import { useRecipeApi } from "./useRecipeApi";
import { sortOptions } from "../constants";

export const useRecipeFilterForm = () => {
    const router = useRouter();
    const pathname = usePathname();
    const { fetchRecipes } = useRecipeApi();
    const listSortOptions = useRecipeStore(state => state.listSortOptions);
    const listFilterOptions = useRecipeStore(state => state.listFilterOptions);
    const { incrementLoadingCount } = useGlobalStore();
    const { control, handleSubmit, getValues, trigger, formState: { errors } } = useForm<RecipeFilterFormData>({
        defaultValues: listFilterOptions,
    });

    /**
     * フォームの送信処理（絞り込みパラメータをURLに含めて遷移）
     * @param data フォームのデータ
     */
    const onSubmit = (data: RecipeFilterFormData, afterSubmit?: () => void) => {
        incrementLoadingCount();
        if (pathname === '/recipe') {
            router.push(`/recipe?${getQueryString(listSortOptions, data)}`);
        } else {
            fetchRecipes(sortOptions.find(v => v.sort === listSortOptions.sort)?.id ?? sortOptions[0].id, data);
        }
        afterSubmit?.();
    };

    return {
        control,
        getValues,
        trigger,
        errors,
        onSubmit: (afterSubmit?: () => void) => handleSubmit(data => onSubmit(data, afterSubmit)),
    };
};