// client/src/hooks/api/useApiErrorHandler.ts

import { useSnackbars } from '@/contexts/useSnackbars'; // スナックバーフックのパスを確認してください
import { AxiosError } from 'axios';

export const useApiErrorHandler = () => {
    const { addSnackbar } = useSnackbars();

    const handleApiError = (error: AxiosError): boolean => {
        // タイムアウトエラー
        if (error.code === 'ECONNABORTED') {
            console.error(error.message);
            addSnackbar('error', 'リクエストがタイムアウトしました');
            return true; // エラーが発生したことを示す
        }

        // バックエンドからのエラーメッセージがある場合
        if (error.response?.data?.message) {
            console.error(error.response.data.message);
            addSnackbar('error', error.response.data.message);
            return true;
        }

        // 予期せぬエラー
        else {
            console.error(error.message);
            addSnackbar('error', '予期せぬエラーが発生しました');
            return true;
        }
    };

    return { handleApiError };
};
