// client/src/hooks/api/useApiErrorHandler.ts
import { AxiosError } from 'axios';

import { useSnackbars } from '../useSnackbars';

export const useApiErrorHandler = () => {
    const { addSnackbar } = useSnackbars();

    const handleApiError = (error: AxiosError): void => {
        // タイムアウトエラー
        if (error.code === 'ECONNABORTED') {
            console.error(error.message);
            addSnackbar('error', 'リクエストがタイムアウトしました');
            return;
        }

        // バックエンドからのエラーメッセージがある場合
        if (error.response?.data?.message) {
            console.error(error.response.data.message);
            addSnackbar('error', error.response.data.message);
            return;
        }

        // 予期せぬエラー
        console.error(error.message);
        addSnackbar('error', '予期せぬエラーが発生しました');
    };

    return { handleApiError };
};
