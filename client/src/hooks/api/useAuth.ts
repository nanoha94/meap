import axios from '@/lib/axios';
import { useParams, useRouter, usePathname } from 'next/navigation';
import React from 'react';
import { useSnackbars } from '@/contexts';

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
                router.push('/plan');
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
        await axios
            .post('/email/verification-notification')
            .then(response => setStatus(response.data.status));
        setIsLoading(false);
    };

    const logout = async () => {
        await axios.post('/logout');
        window.location.pathname = '/login';
    };

    // ログインページから他のページに遷移した時にローディング状態をリセット
    React.useEffect(() => {
        if (isResetLoading && prevPath && prevPath !== pathname) {
            setIsLoading(false);
            setIsResetLoading(false);
            setPrevPath(null);
        }
    }, [prevPath, pathname, isResetLoading]);

    // React.useEffect(() => {
    // TODO: settings/account/page.tsxに移動させる
    // URLからトークンを取得
    // const urlParams = new URLSearchParams(window.location.search);
    // const token = urlParams.get('token');
    // if (token !== null) {
    //     sessionStorage.setItem('invitationToken', token);
    // }
    // TODO: ログイン後のリダイレクト処理を追加する
    // if (middleware === 'guest' && redirectIfAuthenticated && user) {
    //     const token = sessionStorage.getItem('invitationToken');
    //     if (token) {
    //         router.push(`/settings/account?token=${token}`);
    //     } else {
    //         router.push(redirectIfAuthenticated);
    //     }
    // }
    // TODO: page.tsxに移動させる
    // if (
    //     window.location.pathname === '/email/verify' &&
    //     user?.email_verified_at
    // ) {
    //     const token = sessionStorage.getItem('invitationToken');
    //     if (token) {
    //         router.push(`/settings/account?token=${token}`);
    //     } else if (redirectIfAuthenticated) {
    //         router.push(redirectIfAuthenticated);
    //     }
    // }
    // }, []);

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
