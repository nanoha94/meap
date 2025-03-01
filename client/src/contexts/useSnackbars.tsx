'use client';
import { Snackbar } from '@/types';
import React from 'react';
import { v4 as uuidv4 } from 'uuid';

interface SnackbarsContextType {
    snackbars: Snackbar[];
    addSnackbar: (type: Snackbar['type'], message: Snackbar['message']) => void;
    removeSnackbar: (id: string) => void;
}

const SnackbarsContext = React.createContext<SnackbarsContextType>({
    snackbars: [],
    addSnackbar: () => {},
    removeSnackbar: () => {},
});

export const useSnackbars = () => {
    return React.useContext(SnackbarsContext);
};

interface Props {
    children: React.ReactNode;
}

export const SnackbarsProvider: React.FC<Props> = ({ children }) => {
    const [snackbars, setSnackbars] = React.useState<
        {
            id: string;
            message: string;
            type: 'success' | 'error';
            isOpen: boolean;
        }[]
    >([]);

    const addSnackbar = React.useCallback(
        (type: 'success' | 'error', message: string) => {
            const id = uuidv4();
            setSnackbars(prev => {
                const newSnackbars = [
                    ...prev,
                    { id, message, type, isOpen: false },
                ];
                return newSnackbars;
            });

            // 追加後、100ms後に表示（ふわっとアニメーションのため）
            setTimeout(() => {
                setSnackbars(prev =>
                    prev?.map(v => (v.id === id ? { ...v, isOpen: true } : v)),
                );
            }, 100);

            if (type === 'success') {
                setTimeout(() => {
                    removeSnackbar(id);
                }, 10000);
            }
        },
        [],
    );

    const removeSnackbar = React.useCallback((id: string) => {
        setSnackbars(prev =>
            prev?.map(v => (v.id === id ? { ...v, isOpen: false } : v)),
        );

        // 1秒後に削除（ふわっとアニメーションのため）
        setTimeout(() => {
            setSnackbars(prev => prev.filter(v => v.id !== id));
        }, 1000);
    }, []);

    return (
        <SnackbarsContext.Provider
            value={{ snackbars, addSnackbar, removeSnackbar }}>
            {children}
        </SnackbarsContext.Provider>
    );
};
