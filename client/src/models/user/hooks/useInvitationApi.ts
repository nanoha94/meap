import React from 'react';

import { API_STATUS_CODE, TIMEOUT_MS } from '@/constants';
import { useAlertDialog, useApiErrorHandler, useSnackbars } from '@/hooks';
import axios, { isAxiosError } from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import {
    IInvitation,
    IPostInvitaionResponse,
    IPostInvitationJoinResponse,
} from '@/types';
import { DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS, JOIN_ERROR_TYPE } from '../constants';
import { useAccountNavigation } from './useAccountNavigation';

export const useInvitationApi = () => {
    // store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);

    //hook
    const { addSnackbar } = useSnackbars();
    const { openAlertDialog } = useAlertDialog();
    const { removeTokenFromPath } = useAccountNavigation();
    const { handleApiError } = useApiErrorHandler();

    // fetchInvitationTokenのローディング状態（画面全体のローディングアニメーションは動作させたくないためローカル管理）
    const [isFetching, setIsFetching] = React.useState<boolean>(false);
    const [invitationLink, setInvitationLink] = React.useState<string | null>(null);
    const [tokenExpiresAt, setTokenExpiresAt] = React.useState<string | null>(null);

    // 重複リクエスト防止用のフラグ
    const isFetchRequestRef = React.useRef(false);
    const isJoinRequestRef = React.useRef(false);
    /** useCallback 内からの再帰呼び出し用（宣言順の ESLint 回避） */
    const joinGroupRef = React.useRef<
        ((invitationDetail: IInvitation, isDelete: boolean) => Promise<boolean>) | null
    >(null);

    /**
     * 招待トークンを取得する
     * @returns {Promise<{success: boolean}>} 成功/失敗とタイムアウトかどうかの情報
     */
    const fetchInvitationToken = React.useCallback(
        async (
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
                        `${process.env.NEXT_PUBLIC_FRONTEND_URL}/settings/account?token=${responseData.data.token}`,
                    );
                    setTokenExpiresAt(responseData.data.expires_at);
                    return { success: true };
                }
                addSnackbar(
                    'error',
                    responseData.message || '招待リンクの発行に失敗しました',
                );
                return { success: false };
            } catch (error) {
                handleApiError(error);
                onError?.();
                return { success: false };
            } finally {
                isFetchRequestRef.current = false;
                setIsFetching(false);
            }
        },
        [addSnackbar, handleApiError],
    );

    /**
     * グループに参加する
     * @param invitationDetail 招待の詳細（トークンを含む）
     * @param isDelete 削除するかどうか
     * @returns ビジネス上の参加成功時 true（router.refresh / ダイアログを閉じる等は呼び出し側）
     */
    const joinGroup = React.useCallback(
        async (
            invitationDetail: IInvitation,
            isDelete: boolean,
        ): Promise<boolean> => {
            // 重複リクエスト防止
            if (isJoinRequestRef.current) {
                return false;
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
                    addSnackbar(
                        'success',
                        responseData.message || 'リクエストが正常に完了しました',
                    );
                    return true;
                }
                addSnackbar(
                    'error',
                    responseData.message || 'グループへの参加に失敗しました',
                );
                return false;
            } catch (error) {
                // Axiosエラーでない場合はhandleApiErrorに委譲
                if (!isAxiosError(error)) {
                    handleApiError(error);
                    return false;
                }

                // 409エラーかつerror_typeがある場合は、データ消去確認ダイアログを表示する（スナックバーは表示しない）
                if (
                    error.response?.status === API_STATUS_CODE.CONFLICT &&
                    error.response?.data?.error_type != null
                ) {
                    console.error(error.response?.data?.message);

                    const errorType = error.response?.data?.error_type as keyof typeof JOIN_ERROR_TYPE;

                    // ダイアログ表示中も他の参加処理を許可できるよう、この呼び出し分のロックだけ先に解放する（従来の finally タイミングに合わせる）
                    isJoinRequestRef.current = false;
                    decrementLoadingCount();

                    return await new Promise<boolean>(resolve => {
                        openAlertDialog(
                            DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS[errorType],
                            () => {
                                const retryJoinGroup = joinGroupRef.current;
                                if (retryJoinGroup) {
                                    void retryJoinGroup(invitationDetail, true).then(resolve);
                                } else {
                                    resolve(false);
                                }
                            },
                            { onDismiss: () => resolve(false) },
                        );
                    });
                }

                // 409以外のエラー（タイムアウト・その他のHTTPエラー）はhandleApiErrorに委譲
                handleApiError(error);
                return false;
            } finally {
                // 409 競合ブランチでは先にロック解放済みなので二重 decrement しない
                if (isJoinRequestRef.current) {
                    isJoinRequestRef.current = false;
                    decrementLoadingCount();
                }
            }
        },
        [
            addSnackbar,
            decrementLoadingCount,
            handleApiError,
            incrementLoadingCount,
            openAlertDialog,
            removeTokenFromPath,
        ],
    );

    React.useLayoutEffect(() => {
        joinGroupRef.current = joinGroup;
    }, [joinGroup]);

    return {
        isFetching,
        invitationLink,
        tokenExpiresAt,
        fetchInvitationToken,
        joinGroup,
    };
};
