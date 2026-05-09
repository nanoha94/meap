// client/src/hooks/api/useApiErrorHandler.ts
import axios from 'axios';

import { useSnackbars } from '../useSnackbars';

export const useApiErrorHandler = () => {
    const { addSnackbar } = useSnackbars();

    const handleApiError = (error: unknown): void => {
        // Axios 由来のエラーのみタイムアウト・レスポンスを判定（他API利用時と同様にタイムアウトはタイムアウトとして扱う）
        if (axios.isAxiosError(error)) {
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
        }

        // 予期せぬエラー（Axios以外またはメッセージなし）
        const message = error instanceof Error ? error.message : '予期せぬエラーが発生しました';
        console.error(message);
        addSnackbar('error', message);
    };

    return { handleApiError };
};
