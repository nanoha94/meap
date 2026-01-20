import axios from '@/lib/axios';
import { useParams, useRouter } from 'next/navigation';
import { useGlobalStore } from '@/stores';
import { useSnackbars } from '../useSnackbars';

export const useAuth = () => {
    const router = useRouter();
    const params = useParams();
    const { addSnackbar, clearAllSnackbars } = useSnackbars();
    const { setIsLoading } = useGlobalStore();

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
                // ユーザー登録成功時にメール認証ページにリダイレクト
                router.push('/email/verify');
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
                // ログイン成功時にトップ画面にリダイレクト
                window.location.href = '/plan';
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
            });
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
                // パスワードリセット成功時にリセットトークンをクエリパラメータに追加してログインページにリダイレクト
                router.push('/login?reset=' + btoa(response.data.status));
            })
            .catch(error => {
                if (error.response.status !== 422) {
                    setStatus(error.response.data.message);
                } else {
                    setErrors(error.response.data.errors);
                }
                console.error(error);
                addSnackbar('error', error.response.data.message);
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

        // ログインページにリダイレクト
        router.push('/login');
    };

    return {
        register,
        login,
        passwordResetRequest,
        resetPassword,
        resendEmailVerification,
        logout,
    };
};
