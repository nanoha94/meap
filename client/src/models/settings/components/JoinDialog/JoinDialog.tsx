'use client';
import { Dialog, Button, AlertDialog } from '@/components/common';
import { colors } from '@/constants/colors';
import dayjs from 'dayjs';
import { LoaderCircle } from 'lucide-react';
import React, { useEffect } from 'react';
import { useAccountStore } from '../../hooks';
import { useInvitations } from '../../hooks/useInvitations';
import { useAccountHandlers } from '../../hooks/useAccountHandlers';

interface Props {
    token: string;
}

const JoinDialog: React.FC<Props> = ({ token }) => {
    const { dialogs, closeDialog } = useAccountStore();
    const { isOpen } = dialogs.join;
    const [isOpenAlertDialog, setIsOpenAlertDialog] = React.useState(false);
    const [alertDialogConfig, setAlertDialogConfig] = React.useState<{
        message: string;
        alertMessage?: string;
        buttonText: string;
    } | null>(null);
    const { isLoading, invitationDetail, fetchInvitationDetail, joinGroup } =
        useInvitations();
    const { removeTokenFromPath } = useAccountHandlers();

    const { iconAvatar } = useAccountHandlers();

    const isExpired = React.useMemo(
        () => dayjs(invitationDetail?.expires_at) < dayjs(),
        [invitationDetail],
    );

    const fetchInvitation = async () => {
        const result = await fetchInvitationDetail(token);
        if (!result.success) {
            // エラーの場合ダイアログを閉じる
            handleClose();
        }
    };

    const joinGroupWithToken = async (isDelete: boolean = false) => {
        const result = await joinGroup(token, isDelete);
        // データを削除せずグループに参加しようとした場合
        if (!isDelete) {
            // エラーの場合
            if (!result.success) {
                // エラーの種類に応じて処理を分ける
                if (result.errorStatus === 409) {
                    if (result.errorType === 'already_in_group') {
                        setAlertDialogConfig({
                            message:
                                '現在のグループを退出して\n新しいグループに参加しますか？',
                            buttonText: '退出して参加',
                        });
                    } else if (result.errorType === 'has_existing_data') {
                        setAlertDialogConfig({
                            message:
                                'すでに登録済みのデータがあります。\n削除してグループに参加しますか？',
                            alertMessage: '※削除したデータは復元できません',
                            buttonText: '削除して参加',
                        });
                    }
                }
                closeDialog('join');
                setIsOpenAlertDialog(true);
            }
            // 成功した場合
            else {
                handleClose();
            }
        }
        // データを削除してグループに参加しようとした場合
        else {
            handleCloseAlertDialog();
        }
    };

    const handleClose = () => {
        closeDialog('join');
        removeTokenFromPath();
    };

    const handleCloseAlertDialog = () => {
        setIsOpenAlertDialog(false);
        removeTokenFromPath();
    };

    useEffect(() => {
        if (isOpen) {
            fetchInvitation();
        }
    }, [isOpen]);

    return (
        <>
            <Dialog
                title="グループに参加"
                isOpen={isOpen && !!token && token.length > 0}
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
                                            invitationDetail?.inviter.custom_id,
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
                                    onClick={() => joinGroupWithToken(false)}
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
            {/* 削除確認ダイアログ */}
            <AlertDialog
                title="データ削除"
                description={
                    <div className="flex flex-col gap-y-4">
                        <p className="text-center whitespace-pre-wrap">
                            {alertDialogConfig?.message}
                        </p>
                        <span className="text-center text-alert-main">
                            {alertDialogConfig?.alertMessage}
                        </span>
                    </div>
                }
                isLoading={isLoading}
                isOpen={isOpenAlertDialog}
                onClose={handleCloseAlertDialog}
                actionButton={{
                    text: alertDialogConfig?.buttonText ?? '削除して参加',
                    onClick: () => joinGroupWithToken(true),
                }}
            />
        </>
    );
};

export default JoinDialog;
