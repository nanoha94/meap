'use client';
import { useEffect } from 'react';
import { useSnackbars } from '@/contexts';
import { Snackbar } from '@/types';

interface Props {
    type: Snackbar['type'];
    message: Snackbar['message'];
}

const SnackbarHandler = ({ type, message }: Props) => {
    const { addSnackbar } = useSnackbars();

    useEffect(() => {
        if (message) {
            addSnackbar(type, message);
        }
    }, [type, message]);

    return <></>;
};

export default SnackbarHandler;
