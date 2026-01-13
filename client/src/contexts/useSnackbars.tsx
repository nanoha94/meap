'use client';
import { Snackbar } from '@/types';
import React from 'react';
import { usePathname } from 'next/navigation';
import { v4 as uuidv4 } from 'uuid';

interface SnackbarsContextType {
    snackbars: Snackbar[];
    addSnackbar: (type: Snackbar['type'], message: Snackbar['message']) => void;
    removeSnackbar: (id: string) => void;
    clearAllSnackbars: (immediate?: boolean) => void;
}

const SnackbarsContext = React.createContext<SnackbarsContextType>({
    snackbars: [],
    addSnackbar: () => {},
    removeSnackbar: () => {},
    clearAllSnackbars: () => {},
});

export const useSnackbars = () => {
    return React.useContext(SnackbarsContext);
};

interface Props {
    children: React.ReactNode;
}

export const SnackbarsProvider: React.FC<Props> = ({ children }) => {
    const pathname = usePathname();
    const [snackbars, setSnackbars] = React.useState<
        {
            id: string;
            message: string;
            type: 'success' | 'error';
            isOpen: boolean;
        }[]
    >([]);
    const prevPathnameRef = React.useRef<string | null>(null);

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
            } else {
                setTimeout(() => {
                    removeSnackbar(id);
                }, 60000);
            }
        },
        [],
    );

    const removeSnackbar = React.useCallback((id: string) => {
        // まずは非表示にする
        setSnackbars(prev =>
            prev?.map(v => (v.id === id ? { ...v, isOpen: false } : v)),
        );

        // 100ms後に削除（アニメーション完了を待って削除）
        setTimeout(() => {
            setSnackbars(prev => prev.filter(v => v.id !== id));
        }, 100);
    }, []);

    const clearAllSnackbars = React.useCallback(() => {
        // すべて非表示にする
        setSnackbars(prev => prev.map(v => ({ ...v, isOpen: false })));

        // 100ms後に削除（アニメーション完了を待って削除）
        setTimeout(() => {
            setSnackbars([]);
        }, 1000);
    }, []);

    // ページ遷移時にすべてのスナックバーをクリア
    React.useEffect(() => {
        if (
            prevPathnameRef.current !== null &&
            prevPathnameRef.current !== pathname
        ) {
            clearAllSnackbars();
        }
        prevPathnameRef.current = pathname;
    }, [pathname]);

    return (
        <SnackbarsContext.Provider
            value={{
                snackbars,
                addSnackbar,
                removeSnackbar,
                clearAllSnackbars,
            }}>
            {children}
        </SnackbarsContext.Provider>
    );
};
