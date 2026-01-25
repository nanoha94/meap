import { API_STATUS_CODE, TIMEOUT_MS } from '@/constants';
import { useSnackbars } from '@/hooks/useSnackbars';
import { useApiErrorHandler } from '@/hooks/api';
import axios from '@/lib/axios';
import {
    IInvitation,
    IPostInvitaionResponse,
    IPostInvitationJoinResponse,
} from '@/types/api';
import React from 'react';
import { DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS, JOIN_ERROR_TYPE } from '../constants';
import { useAlertDialog } from '@/hooks/useAlertDialog';
import { useAccountHandlers } from './useAccountHandlers';
import { useGlobalStore } from '@/stores';

export const useInvitationApi = () => {
    const { addSnackbar } = useSnackbars();
    const { openAlertDialog } = useAlertDialog();
    const { removeTokenFromPath } = useAccountHandlers();
    const { handleApiError } = useApiErrorHandler();
    const { incrementLoadingCount, decrementLoadingCount } = useGlobalStore();
    // fetchInvitationTokenのローディング状態（画面全体のローディングアニメーションは動作させたくないためローカル管理）
    const [isFetching, setIsFetching] = React.useState<boolean>(false);
    const [invitationLink, setInvitationLink] = React.useState<string | null>(null);
    const [tokenExpiresAt, setTokenExpiresAt] = React.useState<string | null>(null);

    // 重複リクエスト防止用のフラグ
    const isFetchRequestRef = React.useRef(false);
    const isJoinRequestRef = React.useRef(false);

    /**
     * 招待トークンを取得する
     * @returns {Promise<{success: boolean}>} 成功/失敗とタイムアウトかどうかの情報
     */
    const fetchInvitationToken = async (
        onError?: () => void,
    ): Promise<{
        success: boolean;
    }> => {
        // 重複リクエスト防止
        if (isFetchRequestRef.current) {
            return { success: false };
        }

        try {
            isFetchRequestRef.current = true;
            setIsFetching(true);

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
            isFetchRequestRef.current = false;
            setIsFetching(false);
        }
    };

    /**
     * グループに参加する
     * @param token 招待トークン
     * @param isDelete 削除するかどうか
     * @returns {Promise<{success: boolean, errorStatus: number, errorType?: string}>} 成功/失敗の情報
     */
    const joinGroup = async (
        invitationDetail: IInvitation,
        isDelete: boolean,
    ): Promise<void> => {
        // 重複リクエスト防止
        if (isJoinRequestRef.current) {
            return;
        }

        try {
            isJoinRequestRef.current = true;
            incrementLoadingCount();

            const res = await axios.post<IPostInvitationJoinResponse>(
                `/invitations/${invitationDetail.token}/join`,
                {
                    isDelete,
                    timeout: TIMEOUT_MS,
                },
            );
            // URLからトークンを削除
            removeTokenFromPath();

            // レスポンスデータ
            const responseData: IPostInvitationJoinResponse = res.data;

            if (responseData.success) {
                addSnackbar('success', responseData.message);
            }
        } catch (error) {
            // TODO: 見直し（handleApiErrorは使用できないのか？）
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            }
            // 409エラーの場合は、その後データ消去確認ダイアログを表示する（スナックバーは表示しない）
            if (error.response.status === API_STATUS_CODE.CONFLICT && error.response?.data?.error_type) {
                console.error(error.response?.data?.message);

                const errorType = error.response?.data?.error_type as keyof typeof JOIN_ERROR_TYPE;
                openAlertDialog(DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS[errorType], () => {
                    joinGroup(invitationDetail, true);
                });

            } else {
                console.error(error.response?.data?.message);
                addSnackbar('error', error.response?.data?.message || 'エラーが発生しました');

            }
        } finally {
            // ローディングアニメーションを終了
            isJoinRequestRef.current = false;
            decrementLoadingCount();
        }
    };

    return {
        isFetching,
        invitationLink,
        tokenExpiresAt,
        fetchInvitationToken,
        joinGroup,
    };
};
