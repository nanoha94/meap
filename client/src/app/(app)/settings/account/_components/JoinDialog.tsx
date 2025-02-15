import { Dialog } from '@/app/(app)/_components';
import { Button } from '@/components';
import { colors } from '@/constants/colors';
import axios from '@/lib/axios';
import { IGetInvitationDetail } from '@/types/api';
import { Result } from '@dicebear/core';
import dayjs from 'dayjs';
import { LoaderCircle } from 'lucide-react';
import React, { useEffect } from 'react';

interface Props {
    token: string;
    iconAvatar: (id: string) => Result;
    onClose: () => void;
}

const InvitationDialog = ({ token, iconAvatar, onClose }: Props) => {
    const [invitationDetail, setInvitationDetail] =
        React.useState<IGetInvitationDetail | null>(null);

    const fetchInvitationDetail = async () => {
        try {
            const res = await axios.get(`/api/invitation/token/${token}`);
            if (res.data) {
                setInvitationDetail(res.data);
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        }
    };

    const joinGroupWithToken = async () => {
        try {
            const res = await axios.post(`/api/group/users/join/${token}`);
            if (res.data) {
                // TODO: スナックバーで表示
                console.log(res.data.message);
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        }
        onClose();
    };

    useEffect(() => {
        fetchInvitationDetail();
    }, []);

    return (
        <Dialog title="グループに参加" onClose={onClose}>
            {invitationDetail ? (
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
                        <div className="max-w-[230px] w-full flex gap-x-3">
                            <Button
                                colorVariant="gray"
                                variant="outlined"
                                onClick={onClose}>
                                キャンセル
                            </Button>
                            <Button onClick={joinGroupWithToken}>
                                参加する
                            </Button>
                        </div>
                        <div>
                            有効期限：
                            {dayjs(invitationDetail?.expires_at).format(
                                'YYYY年MM月DD日 HH:mm',
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
    );
};

export default InvitationDialog;
