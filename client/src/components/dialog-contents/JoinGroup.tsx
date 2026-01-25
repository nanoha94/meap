'use client';
import React from 'react';
import { Button } from '@/components/common';
import { COLOR_VARIANT } from '@/constants/colors';
import { BUTTON_VARIANT } from '@/constants';
import dayjs from 'dayjs';
import { IInvitation } from '@/types/api';
import { useInvitationApi } from '@/models/settings/hooks';
import { useAccountHandlers } from '@/models/settings/hooks/useAccountHandlers';
import { useDialog } from '@/hooks/useDialog';

interface Props {
    invitationDetail: IInvitation | null;
    isDelete?: boolean;
}

const JoinGroup: React.FC<Props> = ({ invitationDetail, isDelete = false }) => {
    const { joinGroup } = useInvitationApi();
    const { iconAvatar, removeTokenFromPath } = useAccountHandlers();
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
                    {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合は
                                iconsを使用する */}
                    <div
                        className="max-w-[100px] w-full h-auto aspect-square 
                                    rounded-full overflow-hidden"
                        dangerouslySetInnerHTML={{
                            __html: iconAvatar(
                                invitationDetail?.inviter.avatar
                                    .seed ?? '',
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
