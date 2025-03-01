import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';
import { IGetShoppingCategory, IPostShoppingCategory } from '@/types/api';
import React from 'react';
import useSWR from 'swr';

const fetchShoppingCategories = (
    path: string,
): Promise<IGetShoppingCategory[]> => axios.get(path).then(res => res.data);

export const useShoppingCategory = () => {
    const { addSnackbar } = useSnackbars();
    const { data: shoppingCategories, error } = useSWR(
        '/api/group/shopping/categories',
        fetchShoppingCategories,
    );

    const updateShoppingCategories = async (
        categories: IPostShoppingCategory[],
    ) => {
        const filteredCategories = categories?.filter(v => v.name.length > 0);

        for (let idx = 0; idx < filteredCategories.length; idx++) {
            try {
                const res = await axios.post(`/api/group/shopping/categories`, {
                    ...filteredCategories[idx],
                    order: filteredCategories[idx].order ?? idx,
                });
                if (res.data) {
                    addSnackbar('success', res.data.message);
                }
            } catch (error) {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
        }
    };

    const deleteShoppingCategory = async (id: string) => {
        try {
            const res = await axios.delete(`/api/group/shopping/categories`, {
                data: { id },
            });
            if (res.data) {
                addSnackbar('success', res.data.message);
            }
        } catch (error) {
            console.error(error.response?.data.message);
            addSnackbar('error', error.response?.data.message);
        }
    };

    React.useEffect(() => {
        if (error) {
            console.error(error?.response?.data?.message);
            addSnackbar('error', error?.response?.data?.message);
        }
    }, [error]);

    return {
        shoppingCategories,
        updateShoppingCategories,
        deleteShoppingCategory,
    };
};
