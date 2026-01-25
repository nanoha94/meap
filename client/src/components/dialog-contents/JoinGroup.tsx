'use client';
import React from 'react';
import { Button } from '@/components/common';
import { COLOR_VARIANT, colors } from '@/constants/colors';
import { BUTTON_VARIANT } from '@/constants';
import dayjs from 'dayjs';
import { LoaderCircle } from 'lucide-react';
import { IInvitation } from '@/types/api';
import { useInvitationApi } from '@/models/settings/hooks';
import { useAccountHandlers } from '@/models/settings/hooks/useAccountHandlers';
import { useAlertDialog } from '@/hooks/useAlertDialog';
import { useDialog } from '@/hooks/useDialog';
import { useRouter } from 'next/navigation';
import {
    DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS,
    JOIN_ERROR_TYPE,
} from '@/models/settings/constants';

interface Props {
    invitationDetail: IInvitation | null;
}

const JoinGroup: React.FC<Props> = ({ invitationDetail }) => {
    const router = useRouter();
    const { isLoading, joinGroup } = useInvitationApi();
    const { iconAvatar, removeTokenFromPath } = useAccountHandlers();
    const { closeDialog } = useDialog();
    const { openAlertDialog, closeAlertDialog, setAlertDialogLoading } =
        useAlertDialog();
    const token = invitationDetail?.token ?? '';

    /**
     * 招待期限が切れているかどうか
     * @returns
     */
    const isExpired = React.useMemo(
        () => dayjs(invitationDetail?.expires_at) < dayjs(),
        [invitationDetail],
    );

    /**
     * ダイアログを閉じる
     */
    const handleClose = React.useCallback(() => {
        closeDialog();
        removeTokenFromPath();
    }, [closeDialog, removeTokenFromPath]);

    /**
     * handleJoinGroupの最新の参照を保持
     * handleJoinGroupの中でhandleJoinGroupを再帰的に呼び出すために使用する
     */
    const handleJoinGroupRef = React.useRef<((isDelete: boolean) => Promise<void>) | null>(null);

    /**
     * グループに参加する
     * @param isDelete 削除するかどうか
     * @returns
     */
    const handleJoinGroup = React.useCallback(async (isDelete: boolean) => {
        if (!token) return;

        setAlertDialogLoading(true);
        const result = await joinGroup(token, isDelete);
        setAlertDialogLoading(false);

        if (result.success) {
            // 成功した場合
            handleClose();
            router.refresh();
        } else if (result.errorStatus === 409 && result.errorType) {
            // 409エラーの場合、削除確認ダイアログを表示
            const errorType = result.errorType as keyof typeof JOIN_ERROR_TYPE;
            openAlertDialog(DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS[errorType], () => {
                if (handleJoinGroupRef.current) {
                    handleJoinGroupRef.current(true);
                }
            });
            closeDialog();
        } else {
            // その他のエラーの場合
            handleClose();
        }
    },
        [
            token,
            joinGroup,
            setAlertDialogLoading,
            closeAlertDialog,
            handleClose,
            openAlertDialog,
            closeDialog,
            router,
        ],
    );

    /**
     * handleJoinGroupの最新の参照を保持
     */
    React.useEffect(() => {
        handleJoinGroupRef.current = handleJoinGroup;
    }, [handleJoinGroup]);

    return (
        <>
            {!isLoading && invitationDetail ? (
                <div className="flex flex-col items-center gap-y-10">
                    <div className="flex flex-col gap-y-5">
                        <p className="w-full">
                            {invitationDetail?.inviter.name}
                            さんに招待されています。参加しますか？
                        </p>
                        <div className="w-full flex flex-col items-center gap-y-1">
                            {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合は
                                iconsを使用する */}
                            <div
                                className="max-w-[100px] w-full h-auto aspect-square 
                                    rounded-full overflow-hidden"
                                dangerouslySetInnerHTML={{
                                    __html: iconAvatar(
                                        invitationDetail?.inviter.avatar
                                            .seed,
                                    ).toString(),
                                }}
                            />
                            <div className="text-sm">
                                {invitationDetail?.inviter.name}
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-col gap-y-5">
                        <div className="max-w-[320px] w-full flex gap-x-6">
                            <Button
                                colorVariant={COLOR_VARIANT.GRAY}
                                variant={BUTTON_VARIANT.OUTLINED}
                                onClick={handleClose}>
                                キャンセル
                            </Button>
                            <Button
                                onClick={() => handleJoinGroup(false)}
                                disabled={isExpired}>
                                参加する
                            </Button>
                        </div>
                        <div className="flex flex-col items-center">
                            有効期限：
                            {dayjs(invitationDetail?.expires_at).format(
                                'YYYY年MM月DD日 HH:mm',
                            )}
                            {isExpired && (
                                <span className="text-alert-main">
                                    ※ 招待期限が切れています
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            ) : (
                <div className="py-5">
                    <LoaderCircle
                        size={40}
                        color={colors.primary.main}
                        className="animate-spin mx-auto"
                    />
                </div>
            )}
        </>
    );
};

export default JoinGroup;
