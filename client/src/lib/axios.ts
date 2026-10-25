import axios, { isAxiosError, InternalAxiosRequestConfig } from 'axios';

import { API_STATUS_CODE, LINK_TO, TIMEOUT_MS } from '@/constants';

/** 419 リトライ時に二重送信を防ぐためのフラグ */
type RetryableRequestConfig = InternalAxiosRequestConfig & {
    _retried?: boolean;
};

const axiosInstance = axios.create({
    baseURL: process.env.NEXT_PUBLIC_BACKEND_URL,
    timeout: TIMEOUT_MS,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

// セッション期限切れ（例: 2時間放置）で CSRF トークンが無効になると 419 が返る。
// 再ログインなしで復旧できるよう、CSRF トークンを再取得してリクエストを1回だけリトライする。
// API が 503（メンテナンス）のときは、クライアント側の画面を /maintenance へ移す。
axiosInstance.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (!isAxiosError(error)) {
            return Promise.reject(error);
        }

        const originalRequest = error.config as RetryableRequestConfig | undefined;

        // 419 リトライ時に二重送信を防ぐ。
        if (
            error.response?.status === 419 &&
            originalRequest &&
            !originalRequest._retried
        ) {
            originalRequest._retried = true;
            await axiosInstance.get('/sanctum/csrf-cookie');
            return axiosInstance(originalRequest);
        }

        // API が 503（メンテナンス）のとき、クライアント側の画面を /maintenance へ移す。
        // 遷移完了まで呼び出し元の catch を走らせず、エラー表示を出さない。
        if (
            error.response?.status === API_STATUS_CODE.SERVICE_UNAVAILABLE &&
            typeof window !== 'undefined' &&
            window.location.pathname !== LINK_TO.MAINTENANCE
        ) {
            window.location.href = LINK_TO.MAINTENANCE;
            // 遷移完了まで呼び出し元の catch を走らせず、エラー表示を出さない
            return new Promise(() => { });
        }

        return Promise.reject(error);
    },
);

export default axiosInstance;
export { isAxiosError };