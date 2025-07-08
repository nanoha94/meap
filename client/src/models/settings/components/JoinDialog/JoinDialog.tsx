'use client';
import { Dialog, Button } from '@/components/common';
import { colors } from '@/constants/colors';
import dayjs from 'dayjs';
import { LoaderCircle } from 'lucide-react';
import React, { useEffect } from 'react';
import { useAccountStore } from '../../hooks';
import { useAccountHandlers } from '../../hooks/useAccountHandlers';
import { IGetInvitationDetailResponse } from '@/types/api';
interface Props {
    invitationDetail: IGetInvitationDetailResponse | null;
}

const JoinDialog = ({ invitationDetail }: Props) => {
    const { dialogs, openDialog, closeDialog } = useAccountStore();
    const { isOpen } = dialogs.join;
    const { isLoading, removeTokenFromPath, joinGroupWithToken, iconAvatar } =
        useAccountHandlers();

    const isExpired = React.useMemo(
        () => dayjs(invitationDetail?.expires_at) < dayjs(),
        [invitationDetail],
    );

    const handleClose = () => {
        closeDialog('join');
        removeTokenFromPath();
    };

    useEffect(() => {
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
                                            invitationDetail?.inviter.id,
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
                                    onClick={() =>
                                        joinGroupWithToken(
                                            invitationDetail!.token,
                                            false,
                                        )
                                    }
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
        </>
    );
};

export default JoinDialog;
