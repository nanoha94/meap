import axios from '@/lib/axios';
import { useParams, useRouter, usePathname } from 'next/navigation';
import React from 'react';
import { useGlobalStore } from '@/stores';
import { useSnackbars } from '../useSnackbars';

export const useAuth = () => {
    const router = useRouter();
    const params = useParams();
    const pathname = usePathname();
    const { addSnackbar, clearAllSnackbars } = useSnackbars();
    const { setIsLoading } = useGlobalStore();
    const [isResetLoading, setIsResetLoading] = React.useState(false);
    const [prevPath, setPrevPath] = React.useState<string | null>(null);

    const csrf = () => axios.get('/sanctum/csrf-cookie');

    /**
     * ユーザー登録リクエスト
     * @param setErrors エラーを設定する関数
     * @param props ユーザー登録フォームの入力値
     */
    const register = async ({ setErrors, ...props }) => {
        // ローディングアニメーションを開始
        setIsLoading(true);
        // スナックバーをすべて削除
        clearAllSnackbars();
        // エラーをクリア
        setErrors([]);
        // CSRFトークンを取得
        await csrf();

        await axios
            .post('/register', props)
            .then(() => {
                router.push('/email/verify');
                // ローディング状態はuseEffectでパス変化時に自動リセット
                setIsResetLoading(true);
                setPrevPath(pathname);
            })
            .catch(error => {
                if (error.response.status === 422) {
                    setErrors(error.response.data.errors);
                }
                console.error(error);
                addSnackbar(
                    'error',
                    error.response.data.message || 'エラーが発生しました',
                );
            })
            .finally(() => {
                // ローディングアニメーションを終了
                setIsLoading(false);
            });
    };

    /**
     * ログインリクエスト
     * @param setErrors エラーを設定する関数
     * @param setStatus ステータスを設定する関数
     * @param props ログインフォームの入力値
     */
    const login = async ({ setErrors, setStatus, ...props }) => {
        // ローディングアニメーションを開始
        setIsLoading(true);
        // スナックバーをすべて削除
        clearAllSnackbars();
        // エラーをクリア
        setErrors([]);
        // ステータスをクリア
        setStatus(null);
        // CSRFトークンを取得
        await csrf();

        axios
            .post('/login/', props)
            .then(() => {
                router.push('/plan');
                // ローディング状態はuseEffectでパス変化時に自動リセット
                setIsResetLoading(true);
                setPrevPath(pathname);
            })
            .catch(error => {
                if (error.response.status === 422) {
                    setErrors(error.response.data.errors);
                }
                console.error(error);
                addSnackbar(
                    'error',
                    error.response.data.message || 'エラーが発生しました',
                );
            })
            .finally(() => {
                // ローディングアニメーションを終了
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
        // ローディングアニメーションを開始
        setIsLoading(true);
        // スナックバーをすべて削除
        clearAllSnackbars();
        // エラーをクリア
        setErrors([]);
        // ステータスをクリア
        setStatus(null);
        // CSRFトークンを取得
        await csrf();

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
            .finally(() =>
                // ローディングアニメーションを終了
                setIsLoading(false),
            );
    };

    /**
     * パスワードリセットリクエスト
     * @param setErrors エラーを設定する関数
     * @param setStatus ステータスを設定する関数
     * @param props パスワードリセットフォームの入力値
     */
    const resetPassword = async ({ setErrors, setStatus, ...props }) => {
        // ローディングアニメーションを開始
        setIsLoading(true);
        // スナックバーをすべて削除
        clearAllSnackbars();
        // エラーをクリア
        setErrors([]);
        // ステータスをクリア
        setStatus(null);
        // CSRFトークンを取得
        await csrf();

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
            })
            .finally(() => {
                // ローディングアニメーションを終了
                setIsLoading(false);
            });
    };

    /**
     * メールアドレス再送信リクエスト
     * @param setStatus ステータスを設定する関数
     */
    const resendEmailVerification = async ({ setMessage }) => {
        // ローディングアニメーションを開始
        setIsLoading(true);
        // スナックバーをすべて削除
        clearAllSnackbars();
        // CSRFトークンを取得
        await csrf();

        await axios
            .post('/email/verification-notification')
            .then(response => setMessage(response.data.message))
            .catch(error => {
                console.error(error);
                addSnackbar('error', error.response.data.message);
            })
            .finally(() => {
                // ローディングアニメーションを終了
                setIsLoading(false);
            });
    };

    const logout = async () => {
        // ローディングアニメーションを開始
        setIsLoading(true);
        // スナックバーをすべて削除
        clearAllSnackbars();

        await axios.post('/logout');

        // セッションストレージをクリア
        if (typeof window !== 'undefined') {
            sessionStorage.removeItem('redirectAfterLogin');
            // 他のセッション関連データも必要に応じてクリア
        }

        // Laravel側でCookieは削除されるので、ページをリロード
        window.location.href = '/login';
        // ローディングアニメーションを終了
        setIsLoading(false);

        // ログインページにリダイレクト
        router.push('/login');
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
        register,
        login,
        passwordResetRequest,
        resetPassword,
        resendEmailVerification,
        logout,
    };
};
