'use client';
import { Dialog, Button, AlertDialog } from '@/components/common';
import { colors } from '@/constants/colors';
import dayjs from 'dayjs';
import { LoaderCircle } from 'lucide-react';
import React from 'react';
import { useAccountStore } from '../../hooks';
import { useAccountHandlers } from '../../hooks/useAccountHandlers';
import { IGetInvitationDetailResponse } from '@/types/api';
import { useInvitations } from '../../hooks/useInvitations';
import { useRouter } from 'next/navigation';
import {
    DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS,
    JOIN_ERROR_TYPE,
} from '../../constants';
import { AlertDialogConfig, AlertDialogData } from '@/types/dialog';
import { ALERT_DIALOG_STATE_DEFAULT } from '@/constants/dialog';

interface Props {
    invitationDetail: IGetInvitationDetailResponse | null;
}

const JoinDialog = ({ invitationDetail }: Props) => {
    const router = useRouter();
    const { isLoading, joinGroup } = useInvitations();
    const { dialogs, openDialog, closeDialog } = useAccountStore();
    const { isOpen } = dialogs.join;
    const { iconAvatar, removeTokenFromPath } = useAccountHandlers();
    const token = invitationDetail?.token ?? '';

    const [deleteCheckDialog, setDeleteCheckDialog] =
        React.useState<AlertDialogData>(ALERT_DIALOG_STATE_DEFAULT);

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
    const handleClose = () => {
        closeDialog('join');
        removeTokenFromPath();
    };

    /**
     * 削除確認ダイアログを閉じる
     */
    const closeDeleteCheckDialog = () => {
        setDeleteCheckDialog(ALERT_DIALOG_STATE_DEFAULT);
        removeTokenFromPath();
    };

    /**
     * 削除確認ダイアログを開く
     * @param config ダイアログの設定
     */
    const openDeleteCheckDialog = (config: AlertDialogConfig) => {
        setDeleteCheckDialog({
            isOpen: true,
            config,
            onCancel: closeDeleteCheckDialog,
            onAction: () => handleJoinGroup(true),
            isLoading,
        });
    };

    /**
     * グループに参加する
     * @param isDelete 削除するかどうか
     * @returns
     */
    const handleJoinGroup = async (isDelete: boolean) => {
        if (!token) return;

        const result = await joinGroup(token, isDelete);

        if (result.success) {
            // 成功した場合
            closeDialog('join');
            closeDeleteCheckDialog();
            router.refresh();
        } else if (result.errorStatus === 409 && result.errorType) {
            // 409エラーの場合、削除確認ダイアログを表示
            const errorType = result.errorType as keyof typeof JOIN_ERROR_TYPE;
            const config =
                DELETE_CHECK_FOR_JOIN_GROUP_DIALOG_CONFIGS[errorType];
            openDeleteCheckDialog(config);
            closeDialog('join');
        } else {
            // その他のエラーの場合
            closeDialog('join');
            closeDeleteCheckDialog();
        }
    };

    /**
     * 招待メールがある場合、ダイアログを表示
     */
    React.useEffect(() => {
        if (invitationDetail) {
            openDialog('join', undefined);
        }
    }, [invitationDetail]);

    return (
        <>
            <Dialog
                title="グループに参加"
                isOpen={isOpen}
                onClose={handleClose}>
                {!isLoading && invitationDetail ? (
                    <div className="flex flex-col items-center gap-y-10">
                        <div className="flex flex-col gap-y-5">
                            <p className="w-full">
                                {invitationDetail?.inviter.name}
                                さんに招待されています。参加しますか？
                            </p>
                            <div className="w-full flex flex-col items-center gap-y-1">
                                {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                                <div
                                    className="max-w-[100px] w-full h-auto aspect-square rounded-full overflow-hidden"
                                    dangerouslySetInnerHTML={{
                                        __html: iconAvatar(
                                            invitationDetail?.inviter
                                                .avatar_seed,
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
                                    colorVariant="gray"
                                    variant="outlined"
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
            </Dialog>
            <AlertDialog
                isOpen={deleteCheckDialog.isOpen}
                config={deleteCheckDialog.config}
                onCancel={deleteCheckDialog.onCancel}
                onAction={deleteCheckDialog.onAction}
                isLoading={isLoading}
            />
        </>
    );
};

export default JoinDialog;
