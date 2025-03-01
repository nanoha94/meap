import axios from '@/lib/axios';
import { useParams, useRouter } from 'next/navigation';
import useSWR from 'swr';
import { IGetUser } from '@/types/api';
import React from 'react';
import { useSnackbars } from '@/contexts';

interface Props {
    middleware?: 'guest' | 'auth';
    redirectIfAuthenticated?: string;
}

const fetchUser = (path: string): Promise<IGetUser | null> =>
    axios.get(path).then(res => res.data);

export const useAuth = ({
    middleware,
    redirectIfAuthenticated,
}: Props = {}) => {
    const router = useRouter();
    const params = useParams();
    const { addSnackbar } = useSnackbars();
    const { data: user, error, mutate } = useSWR('/api/user', fetchUser);

    const csrf = () => axios.get('/sanctum/csrf-cookie');

    const register = async ({ setErrors, ...props }) => {
        await csrf();

        setErrors([]);

        axios
            .post('/register', props)
            .then(() => mutate())
            .catch(error => {
                if (error.response.status !== 422) throw error;

                setErrors(error.response.data.errors);
            });
    };

    const login = async ({ setErrors, setStatus, ...props }) => {
        await csrf();

        setErrors([]);
        setStatus(null);

        axios
            .post('/login', props)
            .then(() => mutate())
            .catch(error => {
                if (error.response.status !== 422) throw error;

                setErrors(error.response.data.errors);
            });
    };

    const passwordResetRequest = async ({ setErrors, setStatus, email }) => {
        await csrf();

        setErrors([]);
        setStatus(null);

        axios
            .post('/password/reset/request', { email })
            .then(response => setStatus(response.data.status))
            .catch(error => {
                if (error.response.status !== 422) {
                    setStatus(error.response.data.message);
                } else {
                    setErrors(error.response.data.errors);
                }
            });
    };

    const resetPassword = async ({ setErrors, setStatus, ...props }) => {
        await csrf();

        setErrors([]);
        setStatus(null);

        axios
            .post('/password/reset', { token: params.token, ...props })
            .then(response => {
                router.push('/login?reset=' + btoa(response.data.status));
            })
            .catch(error => {
                if (error.response.status !== 422) {
                    setStatus(error.response.data.message);
                } else {
                    setErrors(error.response.data.errors);
                }
            });
    };

    const resendEmailVerification = ({ setStatus }) => {
        axios
            .post('/email/verification-notification')
            .then(response => setStatus(response.data.status));
    };

    const logout = async () => {
        if (!error) {
            await axios.post('/logout').then(() => mutate());
        }

        window.location.pathname = '/login';
    };

    React.useEffect(() => {
        // URLからトークンを取得
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        if (token !== null) {
            sessionStorage.setItem('invitationToken', token);
        }

        if (middleware === 'guest' && redirectIfAuthenticated && user) {
            const token = sessionStorage.getItem('invitationToken');
            if (token) {
                router.push(`/settings/account?token=${token}`);
            } else {
                router.push(redirectIfAuthenticated);
            }
        }

        if (middleware === 'auth') {
            // 5秒待ってもユーザ情報が取得できない場合はログインページへリダイレクト
            // TODO: zustandでユーザを管理する？
            if (!user) {
                const timer = setTimeout(() => {
                    if (!user) {
                        router.push('/login');
                    }
                }, 5000);
                return () => clearTimeout(timer);
            }
            if (user && !user.email_verified_at) {
                router.push('/email/verify');
            }
        }

        if (
            window.location.pathname === '/email/verify' &&
            user?.email_verified_at
        ) {
            const token = sessionStorage.getItem('invitationToken');
            if (token) {
                router.push(`/settings/account?token=${token}`);
            } else {
                router.push(redirectIfAuthenticated);
            }
        }

        if (middleware === 'auth' && error) {
            logout();
            addSnackbar('error', error.response?.data.message);
        }
    }, [user, error]);

    return {
        user,
        register,
        login,
        passwordResetRequest,
        resetPassword,
        resendEmailVerification,
        logout,
    };
};
