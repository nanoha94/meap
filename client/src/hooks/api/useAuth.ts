import axios from '@/lib/axios';
import { useParams, useRouter, usePathname } from 'next/navigation';
import React from 'react';
import { useSnackbars } from '@/contexts';

// Cookie操作のヘルパー関数
const getCookie = (name: string): string | null => {
    if (typeof window === 'undefined') return null;
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop()?.split(';').shift() || null;
    return null;
};

const deleteCookie = (name: string): void => {
    if (typeof window === 'undefined') return;
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
};

export const useAuth = () => {
    const router = useRouter();
    const params = useParams();
    const pathname = usePathname();
    const { addSnackbar } = useSnackbars();
    const [isLoading, setIsLoading] = React.useState(false);
    const [isResetLoading, setIsResetLoading] = React.useState(false);
    const [prevPath, setPrevPath] = React.useState<string | null>(null);

    const csrf = () => axios.get('/sanctum/csrf-cookie');

    /**
     * ユーザー登録リクエスト
     * @param setErrors エラーを設定する関数
     * @param props ユーザー登録フォームの入力値
     */
    const register = async ({ setErrors, ...props }) => {
        setIsLoading(true);
        await csrf();

        setErrors([]);

        await axios
            .post('/register', props)
            .then(() => {
                router.push('/email/verify');
                // ローディング状態はuseEffectでパス変化時に自動リセット
                setIsResetLoading(true);
                setPrevPath(pathname);
            })
            .catch(error => {
                if (error.response.status !== 422) throw error;
                setErrors(error.response.data.errors);
                console.error(error);
                addSnackbar('error', error.response.data.message);
            });

        setIsLoading(false);
    };

    /**
     * ログインリクエスト
     * @param setErrors エラーを設定する関数
     * @param setStatus ステータスを設定する関数
     * @param props ログインフォームの入力値
     */
    const login = async ({ setErrors, setStatus, ...props }) => {
        setIsLoading(true);
        await csrf();

        setErrors([]);
        setStatus(null);

        axios
            .post('/login/', props)
            .then(() => {
                // セッションストレージからリダイレクトURLを取得
                const sessionRedirectUrl =
                    sessionStorage.getItem('redirectAfterLogin');

                // cookieからリダイレクトURLを取得
                const cookieRedirectUrl = getCookie('redirectPath');

                if (sessionRedirectUrl) {
                    // セッションストレージからのリダイレクト（優先）
                    sessionStorage.removeItem('redirectAfterLogin');
                    window.location.href = sessionRedirectUrl;
                } else if (cookieRedirectUrl) {
                    // cookieからのリダイレクト
                    deleteCookie('redirectPath');
                    window.location.href = cookieRedirectUrl;
                } else {
                    // デフォルトのリダイレクト先
                    router.push('/plan');
                }

                // ローディング状態はuseEffectでパス変化時に自動リセット
                setIsResetLoading(true);
                setPrevPath(pathname);
            })
            .catch(error => {
                if (error.response.status !== 422) throw error;
                setErrors(error.response.data.errors);
                console.error(error);
                addSnackbar('error', error.response.data.message);
                setIsLoading(false);
            });
    };

    /**
     * パスワードリセットリクエスト
     * @param setErrors エラーを設定する関数
     * @param setStatus ステータスを設定する関数
     * @param email メールアドレス
     */
    const passwordResetRequest = async ({ setErrors, setStatus, email }) => {
        setIsLoading(true);
        await csrf();

        setErrors([]);
        setStatus(null);

        await axios
            .post('/password/reset/request', { email })
            .then(response => setStatus(response.data.status))
            .catch(error => {
                if (error.response.status !== 422) {
                    setStatus(error.response.data.message);
                } else {
                    setErrors(error.response.data.errors);
                }
                console.error(error);
                addSnackbar('error', error.response.data.message);
            })
            .finally(() => setIsLoading(false));
    };

    /**
     * パスワードリセットリクエスト
     * @param setErrors エラーを設定する関数
     * @param setStatus ステータスを設定する関数
     * @param props パスワードリセットフォームの入力値
     */
    const resetPassword = async ({ setErrors, setStatus, ...props }) => {
        setIsLoading(true);
        await csrf();

        setErrors([]);
        setStatus(null);

        await axios
            .post('/password/reset', { token: params?.token, ...props })
            .then(response => {
                router.push('/login?reset=' + btoa(response.data.status));
                // ローディング状態はuseEffectでパス変化時に自動リセット
                setIsResetLoading(true);
                setPrevPath(pathname);
            })
            .catch(error => {
                if (error.response.status !== 422) {
                    setStatus(error.response.data.message);
                } else {
                    setErrors(error.response.data.errors);
                }
                console.error(error);
                addSnackbar('error', error.response.data.message);
                setIsLoading(false);
            });
    };

    /**
     * メールアドレス再送信リクエスト
     * @param setStatus ステータスを設定する関数
     */
    const resendEmailVerification = async ({ setStatus }) => {
        setIsLoading(true);
        await csrf();

        await axios
            .post('/email/verification-notification')
            .then(response => setStatus(response.data.status))
            .catch(error => {
                console.error(error);
                addSnackbar('error', error.response.data.message);
            });
        setIsLoading(false);
    };

    const logout = async () => {
        setIsLoading(true);
        await axios.post('/logout');

        // セッションストレージをクリア
        if (typeof window !== 'undefined') {
            sessionStorage.removeItem('redirectAfterLogin');
            // 他のセッション関連データも必要に応じてクリア
        }

        // Laravel側でCookieは削除されるので、ページをリロード
        window.location.href = '/login';
        setIsLoading(false);
    };

    // ログインページから他のページに遷移した時にローディング状態をリセット
    React.useEffect(() => {
        if (isResetLoading && prevPath && prevPath !== pathname) {
            setIsLoading(false);
            setIsResetLoading(false);
            setPrevPath(null);
        }
    }, [prevPath, pathname, isResetLoading]);

    return {
        isLoading,
        register,
        login,
        passwordResetRequest,
        resetPassword,
        resendEmailVerification,
        logout,
    };
};
