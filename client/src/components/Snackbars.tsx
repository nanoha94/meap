'use client';
import React from 'react';
import { colors } from '@/constants/colors';
import { AlertTriangle, CircleCheck, X } from 'lucide-react';
import { useSnackbars } from '@/contexts';
import itemOpenStyles from '@/styles/itemOpen.module.css';
import { Snackbar as SnackbarType } from '@/types';

const bgColorClass = (type: 'success' | 'error') => {
    if (type === 'success') {
        return 'bg-success-background';
    }
    return 'bg-alert-background';
};

const textColorClass = (type: 'success' | 'error') => {
    if (type === 'success') {
        return 'text-success-main';
    }
    return 'text-alert-main';
};
const borderColorClass = (type: 'success' | 'error') => {
    if (type === 'success') {
        return 'border-success-main';
    }
    return 'border-alert-main';
};

const Snackbars = () => {
    const { snackbars, removeSnackbar } = useSnackbars();

    return (
        <div className="fixed bottom-10 right-0 p-3 max-w-[600px] w-fit flex flex-col gap-y-2 justify-center">
            {snackbars?.map(v => (
                <Snackbar
                    key={v.id}
                    snackbar={v}
                    removeSnackbar={() => removeSnackbar(v.id)}
                />
            ))}
        </div>
    );
};
export default Snackbars;

interface SnackbarProps {
    snackbar: SnackbarType;
    removeSnackbar: () => void;
}

const Snackbar = ({ snackbar, removeSnackbar }: SnackbarProps) => {
    return (
        <div
            className={`py-3 pl-5 pr-3 flex justify-between gap-x-8 ${textColorClass(snackbar.type)} ${bgColorClass(snackbar.type)} border-2 ${borderColorClass(snackbar.type)} rounded-lg ${snackbar.isOpen ? itemOpenStyles.open : itemOpenStyles.close}`}>
            <div className="flex gap-x-2">
                {snackbar.type === 'success' ? (
                    <CircleCheck />
                ) : (
                    <AlertTriangle />
                )}
                <p className="whitespace-pre-line">{snackbar.message}</p>
            </div>
            <button onClick={removeSnackbar} className="h-fit">
                <X color={colors.black} />
            </button>
        </div>
    );
};
