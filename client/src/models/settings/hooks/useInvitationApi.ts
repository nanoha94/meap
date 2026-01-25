import { TIMEOUT_MS } from '@/constants';
import { useSnackbars } from '@/hooks/useSnackbars';
import { useApiErrorHandler } from '@/hooks/api';
import axios from '@/lib/axios';
import {
    IPostInvitaionResponse,
    IPostInvitationJoinResponse,
} from '@/types/api';
import React from 'react';

export const useInvitationApi = () => {
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const [isLoading, setIsLoading] = React.useState(false);
    const [invitationLink, setInvitationLink] = React.useState<string | null>(
        null,
    );
    const [tokenExpiresAt, setTokenExpiresAt] = React.useState<string | null>(
        null,
    );

    /**
     * 招待トークンを取得する
     * @returns {Promise<{success: boolean}>} 成功/失敗とタイムアウトかどうかの情報
     */
    const fetchInvitationToken = async (
        onError?: () => void,
    ): Promise<{
        success: boolean;
    }> => {
        try {
            setIsLoading(true);

            // 招待トークン発行
            const res = await axios.post<IPostInvitaionResponse>(
                '/invitations',
                {
                    timeout: TIMEOUT_MS,
                },
            );

            // レスポンスデータ
            const responseData: IPostInvitaionResponse = res.data;

            if (responseData.success) {
                setInvitationLink(
                    `${process.env.NEXT_PUBLIC_FRONT_URL}/settings/account?token=${responseData.data.token}`,
                );
                setTokenExpiresAt(responseData.data.expires_at);
            }
            return { success: true };
        } catch (error) {
            handleApiError(error);
            onError?.();
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
            const res = await axios.post<IPostInvitationJoinResponse>(
                `/invitations/${token}/join`,
                {
                    isDelete,
                    timeout: TIMEOUT_MS,
                },
            );

            // レスポンスデータ
            const responseData: IPostInvitationJoinResponse = res.data;

            if (responseData.success) {
                addSnackbar('success', responseData.message);
            }
            return { success: true };
        } catch (error) {
            // TODO: 見直し（handleApiErrorは使用できないのか？）
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
                return { success: false, errorStatus: 408 };
            }
            // 409エラーの場合は、その後データ消去確認ダイアログを表示するので、スナックバーは表示しない
            else if (error.response.status === 409 && error.response?.data.error_type) {
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
        fetchInvitationToken,
        joinGroup,
    };
};
