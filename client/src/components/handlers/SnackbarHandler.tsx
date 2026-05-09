'use client';
import React from 'react';

import { useSnackbars } from '@/hooks';
import { Snackbar } from '@/types';

interface Props {
    type: Snackbar['type'];
    message: Snackbar['message'];
}

const SnackbarHandler = ({ type, message }: Props) => {
    const { addSnackbar } = useSnackbars();
    const previousMessageRef = React.useRef<string | null>(null);

    React.useEffect(() => {
        // メッセージが存在し、前回のメッセージと異なる場合のみ追加
        if (message && message !== previousMessageRef.current) {
            addSnackbar(type, message);
            previousMessageRef.current = message;
        }
    }, [type, message, addSnackbar]);

    return <></>;
};

export default SnackbarHandler;
