"use client";

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import axios from '@/lib/axios';

import { API_STATUS_CODE } from '@/constants';
import { useGlobalStore } from '@/stores';
import { useApiErrorHandler } from './useApiErrorHandler';
import { useSnackbars } from '../useSnackbars';

interface RegisterProps {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    setErrors: (errors: Record<string, string[]>) => void;
}

interface LoginProps {
    email: string;
    password: string;
    remember: boolean;
    setErrors: (errors: Record<string, string[]>) => void;
    setStatus: (status: string | null) => void;
}

interface PasswordResetRequestProps {
    email: string;
    setErrors: (errors: Record<string, string[]>) => void;
    setStatus: (status: string | null) => void;
}

interface ResetPasswordProps {
    password: string;
    password_confirmation: string;
    setErrors: (errors: Record<string, string[]>) => void;
    setStatus: (status: string | null) => void;
}

interface ResendEmailVerificationProps {
    setMessage: (message: string) => void;
}

export const useAuth = () => {
    //store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);

    // hook
    const router = useRouter();
    const params = useParams();
    const { clearAllSnackbars } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    const csrf = () => axios.get('/sanctum/csrf-cookie');

    // 重複リクエスト防止用のフラグ
    const isRegisterRequestRef = React.useRef(false);
    const isLoginRequestRef = React.useRef(false);
    const isPasswordResetRequestRef = React.useRef(false);
    const isResetPasswordRequestRef = React.useRef(false);
    const isResendEmailVerificationRequestRef = React.useRef(false);
    const isLogoutRequestRef = React.useRef(false);

    /**
     * ユーザー登録リクエスト
     * @param setErrors エラーを設定する関数
     * @param props ユーザー登録フォームの入力値
     */
    const register = async ({ setErrors, ...props }: RegisterProps) => {
        // 重複リクエスト防止
        if (isRegisterRequestRef.current) {
            return;
        }

        try {
            // 重複リクエスト防止用のフラグをセット
            isRegisterRequestRef.current = true;
            // ローディングカウントを増やす
            incrementLoadingCount();
            // スナックバーをすべて削除
            clearAllSnackbars();
            // エラーをクリア
            setErrors({});
            // CSRFトークンを取得
            await csrf();

            await axios.post('/register', props);
            // ユーザー登録成功時にメール認証ページにリダイレクト
            router.push('/email/verify');
        } catch (error) {
            if (error.response?.status === API_STATUS_CODE.UNPROCESSABLE_ENTITY) {
                setErrors(error.response.data.errors);
            } else {
                handleApiError(error);
            }
            // エラーの時は画面遷移がないのでローディングカウントを減らす
            decrementLoadingCount();
        } finally {
            // 重複リクエスト防止用のフラグをリセット
            isRegisterRequestRef.current = false;
            // 画面遷移後にローディングカウントをリセットするので、ここでは減らさない
        }
    };

    /**
     * ログインリクエスト
     * @param setErrors エラーを設定する関数
     * @param setStatus ステータスを設定する関数
     * @param props ログインフォームの入力値
     */
    const login = async ({ setErrors, setStatus, ...props }: LoginProps) => {
        // 重複リクエスト防止
        if (isLoginRequestRef.current) {
            return;
        }

        try {
            isLoginRequestRef.current = true;
            // ローディングアニメーションを開始
            incrementLoadingCount();
            // スナックバーをすべて削除
            clearAllSnackbars();
            // エラーをクリア
            setErrors({});
            // ステータスをクリア
            setStatus(null);
            // CSRFトークンを取得
            await csrf();

            await axios.post('/login/', props);
            // ログイン成功時にトップ画面にリダイレクト
            window.location.href = '/plan';
        } catch (error) {
            if (error.response?.status === API_STATUS_CODE.UNPROCESSABLE_ENTITY) {
                setErrors(error.response.data.errors);
            } else {
                handleApiError(error);
            }
            // エラーの時は画面遷移がないのでローディングカウントを減らす
            decrementLoadingCount();
        } finally {
            // 重複リクエスト防止用のフラグをリセット
            isLoginRequestRef.current = false;
            // 画面遷移後にローディングカウントをリセットするので、ここでは減らさない
        }
    };

    /**
     * パスワードリセットリクエスト
     * @param setErrors エラーを設定する関数
     * @param setStatus ステータスを設定する関数
     * @param email メールアドレス
     */
    const passwordResetRequest = async ({
        setErrors,
        setStatus,
        email,
    }: PasswordResetRequestProps) => {
        // 重複リクエスト防止
        if (isPasswordResetRequestRef.current) {
            return;
        }

        try {
            // 重複リクエスト防止用のフラグをセット
            isPasswordResetRequestRef.current = true;
            // ローディングカウントを増やす
            incrementLoadingCount();
            // スナックバーをすべて削除
            clearAllSnackbars();
            // エラーをクリア
            setErrors({});
            // ステータスをクリア
            setStatus(null);
            // CSRFトークンを取得
            await csrf();

            const response = await axios.post('/password/reset/request', { email });
            setStatus(response.data.message);
        } catch (error) {
            if (error.response?.status === API_STATUS_CODE.UNPROCESSABLE_ENTITY) {
                setErrors({ email: [error.response.data.message] });
            } else {
                setStatus(error.response?.data?.message);
            }
            console.error({ email: [error.response?.data?.message] });
            // エラーの時は画面遷移がないのでローディングカウントを減らす
            decrementLoadingCount();
        } finally {
            // 重複リクエスト防止用のフラグをリセット
            isPasswordResetRequestRef.current = false;
            // ローディングカウントを減らす
            decrementLoadingCount();
        }
    };

    /**
     * パスワードリセットリクエスト
     * @param setErrors エラーを設定する関数
     * @param setStatus ステータスを設定する関数
     * @param props パスワードリセットフォームの入力値
     */
    const resetPassword = async ({ setErrors, setStatus, ...props }: ResetPasswordProps) => {
        // 重複リクエスト防止
        if (isResetPasswordRequestRef.current) {
            return;
        }

        try {
            // 重複リクエスト防止用のフラグをセット
            isResetPasswordRequestRef.current = true;
            // ローディングカウントを増やす
            incrementLoadingCount();
            // スナックバーをすべて削除
            clearAllSnackbars();
            // エラーをクリア
            setErrors({});
            // ステータスをクリア
            setStatus(null);
            // CSRFトークンを取得
            await csrf();

            const response = await axios.post('/password/reset', { token: params?.token, ...props });
            // パスワードリセット成功時にリセットトークンをクエリパラメータに追加してログインページにリダイレクト
            router.push('/login?reset=' + btoa(response.data.message));
        } catch (error) {
            if (error.response?.status === API_STATUS_CODE.UNPROCESSABLE_ENTITY) {
                setErrors(error.response.data.errors);
            } else {
                setStatus(error.response?.data?.message);
            }
            console.error(error.response?.data?.message);
            // エラーの時は画面遷移がないのでローディングカウントを減らす
            decrementLoadingCount();
        } finally {
            // 重複リクエスト防止用のフラグをリセット
            isResetPasswordRequestRef.current = false;
            // 画面遷移後にローディングカウントをリセットするので、ここでは減らさない
        }
    };

    /**
     * メールアドレス再送信リクエスト
     * @param setMessage メッセージを設定する関数
     */
    const resendEmailVerification = async ({ setMessage }: ResendEmailVerificationProps) => {
        // 重複リクエスト防止
        if (isResendEmailVerificationRequestRef.current) {
            return;
        }

        try {
            // 重複リクエスト防止用のフラグをセット
            isResendEmailVerificationRequestRef.current = true;
            // ローディングカウントを増やす
            incrementLoadingCount();
            // スナックバーをすべて削除
            clearAllSnackbars();
            // CSRFトークンを取得
            await csrf();

            const response = await axios.post('/email/verification-notification');
            setMessage(response.data.message);
        } catch (error) {
            handleApiError(error);
        } finally {
            // 重複リクエスト防止用のフラグをリセット
            isResendEmailVerificationRequestRef.current = false;
            // ローディングカウントを減らす
            decrementLoadingCount();
        }
    };

    const logout = async () => {
        // 重複リクエスト防止
        if (isLogoutRequestRef.current) {
            return;
        }

        try {
            // 重複リクエスト防止用のフラグをセット
            isLogoutRequestRef.current = true;
            // ローディングカウントを増やす
            incrementLoadingCount();
            // スナックバーをすべて削除
            clearAllSnackbars();
            // CSRFトークンを取得
            await csrf();

            await axios.post('/logout');

            // セッションストレージをクリア
            if (typeof window !== 'undefined') {
                sessionStorage.clear();
            }

            // ログインページへ遷移（Laravel側でCookieは削除される）
            window.location.href = '/login';
        } catch (error) {
            handleApiError(error);
            // エラーが発生してもログインページへ遷移
            window.location.href = '/login';
        } finally {
            // 重複リクエスト防止用のフラグをリセット
            isLogoutRequestRef.current = false;
            // 画面遷移後にローディングカウントをリセットするので、ここでは減らさない
        }
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
