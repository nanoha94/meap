import { timeout_ms } from '@/constants';
import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';
import { IGetInvitationDetailResponse } from '@/types/api';
import React from 'react';

export const useInvitations = () => {
    const { addSnackbar } = useSnackbars();
    const [isLoading, setIsLoading] = React.useState(false);
    const [invitationLink, setInvitationLink] = React.useState<string | null>(
        null,
    );
    const [tokenExpiresAt, setTokenExpiresAt] = React.useState<string | null>(
        null,
    );
    const [invitationDetail, setInvitationDetail] =
        React.useState<IGetInvitationDetailResponse | null>(null);

    /**
     * 招待トークンを取得する
     * @returns {Promise<{success: boolean}>} 成功/失敗とタイムアウトかどうかの情報
     */
    const fetchInvitationToken = async (): Promise<{
        success: boolean;
    }> => {
        try {
            setIsLoading(true);
            const res = await axios.post('/invitations', {
                timeout: timeout_ms,
            });

            if (res.data) {
                setInvitationLink(
                    `${process.env.NEXT_PUBLIC_FRONT_URL}/settings/account?token=${res.data.token}`,
                );
                setTokenExpiresAt(res.data.expires_at);
            }
            return { success: true };
        } catch (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
            return { success: false };
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * 招待詳細を取得する
     * @param token 招待トークン
     * @returns {Promise<{success: boolean}>} 成功/失敗の情報
     */
    const fetchInvitationDetail = async (
        token: string,
    ): Promise<{
        success: boolean;
    }> => {
        setIsLoading(true);
        setInvitationDetail(null);
        try {
            const res = await axios.get(`/invitations/${token}`, {
                timeout: timeout_ms,
            });
            if (res.data) {
                setInvitationDetail(res.data);
            }
            return { success: true };
        } catch (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
            return { success: false };
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * グループに参加する
     * @param token 招待トークン
     * @param isDelete 削除するかどうか
     * @returns {Promise<{success: boolean, errorStatus: number, errorType?: string}>} 成功/失敗の情報
     */
    const joinGroup = async (
        token: string,
        isDelete: boolean,
    ): Promise<{
        success: boolean;
        errorStatus?: number;
        errorType?: string;
    }> => {
        setIsLoading(true);
        try {
            const res = await axios.post(`/invitations/${token}/join`, {
                isDelete,
                timeout: timeout_ms,
            });

            if (res.data) {
                addSnackbar('success', res.data.message);
            }
            return { success: true };
        } catch (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
                return { success: false, errorStatus: 408 };
            }
            // 409エラーの場合は、その後データ消去確認ダイアログを表示するので、スナックバーは表示しない
            else if (error.response.status === 409) {
                console.error(error.response?.data.message);
                return {
                    success: false,
                    errorStatus: error.response.status,
                    errorType: error.response?.data.error_type,
                };
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
                return {
                    success: false,
                    errorStatus: error.response.status,
                    errorType: error.response?.data.error_type,
                };
            }
        } finally {
            setIsLoading(false);
        }
    };

    return {
        isLoading,
        invitationLink,
        tokenExpiresAt,
        invitationDetail,
        fetchInvitationToken,
        fetchInvitationDetail,
        joinGroup,
    };
};
