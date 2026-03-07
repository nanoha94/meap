'use client';
import React from 'react';
import dayjs from 'dayjs';

import { Button } from '@/components';
import { BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';
import { useDialog } from '@/hooks';
import { useAccountNavigation, useInvitationApi, iconAvatar } from '@/models/user';
import { IInvitation } from '@/types';
import Image from 'next/image';

interface Props {
    invitationDetail: IInvitation | null;
    isDelete?: boolean;
}

const JoinGroup: React.FC<Props> = ({ invitationDetail, isDelete = false }) => {
    const { joinGroup } = useInvitationApi();
    const { removeTokenFromPath } = useAccountNavigation();
    const { closeDialog } = useDialog();

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
     * グループに参加する
     * @param isDelete 削除するかどうか
     * @returns
     */
    const handleJoinGroup = React.useCallback(async () => {
        if (!invitationDetail) return;

        joinGroup(invitationDetail, isDelete);
        closeDialog();
    }, [invitationDetail, joinGroup, closeDialog]);


    return (

        <div className="flex flex-col items-center gap-y-10">
            <div className="flex flex-col gap-y-5">
                <p className="w-full">
                    {invitationDetail?.inviter.name}
                    さんに招待されています。参加しますか？
                </p>
                <div className="w-full flex flex-col items-center gap-y-1">
                    <div className="max-w-[100px] w-full h-auto aspect-square 
                                    rounded-full overflow-hidden">
                        {invitationDetail?.inviter?.avatar?.image ? (
                            <Image
                                src={invitationDetail?.inviter.avatar.image.src}
                                alt="avatar"
                                width={invitationDetail?.inviter.avatar.image.width}
                                height={invitationDetail?.inviter.avatar.image.height}
                                className="w-full h-full object-cover"
                            />
                        ) : (
                            <div

                                dangerouslySetInnerHTML={{
                                    __html: iconAvatar(
                                        invitationDetail?.inviter.avatar
                                            .seed ?? '',
                                    ).toString(),
                                }}
                            />)}
                    </div>
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
                        onClick={() => handleJoinGroup()}
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
    );
};

export default JoinGroup;
