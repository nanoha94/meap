import axios, { isAxiosError, InternalAxiosRequestConfig } from 'axios';

/** 419 リトライ時に二重送信を防ぐためのフラグ */
type RetryableRequestConfig = InternalAxiosRequestConfig & {
    _retried?: boolean;
};

const axiosInstance = axios.create({
    baseURL: process.env.NEXT_PUBLIC_BACKEND_URL,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

// セッション期限切れ（例: 2時間放置）で CSRF トークンが無効になると 419 が返る。
// 再ログインなしで復旧できるよう、CSRF トークンを再取得してリクエストを1回だけリトライする。
axiosInstance.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (!isAxiosError(error)) {
            return Promise.reject(error);
        }

        const originalRequest = error.config as RetryableRequestConfig | undefined;

        if (
            error.response?.status === 419 &&
            originalRequest &&
            !originalRequest._retried
        ) {
            originalRequest._retried = true;
            await axiosInstance.get('/sanctum/csrf-cookie');
            return axiosInstance(originalRequest);
        }

        return Promise.reject(error);
    },
);

export default axiosInstance;
export { isAxiosError };