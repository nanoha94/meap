'use client';
import { icons } from '@dicebear/collection';
import { createAvatar } from '@dicebear/core';
import { Dialog, TextButton } from '../../_components';
import { ChevronRight, Copy, LoaderCircle } from 'lucide-react';
import { useAuth } from '@/hooks';
import React from 'react';
import { QRCodeSVG } from 'qrcode.react';
import axios from '@/lib/axios';
import { colors } from '@/constants/colors';
import dayjs from 'dayjs';
import { GroupUser } from '@/types/user';
import useSWR from 'swr';

const fetchGroupUsers = (): Promise<GroupUser[]> =>
    axios.get('/api/group/users').then(res => res.data);

const Page = () => {
    const { user } = useAuth();
    const { data: users, error } = useSWR('/api/group/users', fetchGroupUsers);
    const [invitationLink, setInvitationLink] = React.useState<string | null>(
        null,
    );
    const [tokenExpiresAt, setTokenExpiresAt] = React.useState<string | null>(
        null,
    );
    const [isOpenInviteDialog, setIsOpenInviteDialog] =
        React.useState<boolean>(false);

    const iconAvatar = (id: string) =>
        createAvatar(icons, {
            seed: id,
            backgroundColor: [
                'b6e3f4', // 水色
                'ffd5dc', // ピンク
                'd1f7c4', // 黄緑
                'f4d03f', // 黄色
                'ffcfab', // オレンジ
                'bdc3c7', // グレー
                'e8daef', // 薄紫
                'aed6f1', // 青
            ],
        });

    const copyToClipboard = async (link: string) => {
        try {
            await navigator.clipboard.writeText(link);
        } catch (err) {
            console.error(err);
        }
    };

    const fetchInvitationToken = async () => {
        setInvitationLink(null);
        try {
            const res = await axios.get('/api/invitation');
            if (res.data) {
                setInvitationLink(
                    `${process.env.NEXT_PUBLIC_BACKEND_URL}/invitation/${res.data.token}`,
                );
                setTokenExpiresAt(res.data.expires_at);
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        }
    };

    React.useEffect(() => {
        if (error) {
            // TODO: スナックバーでエラー表示
            console.error(error?.response?.data?.message);
        }
    }, [error]);

    return (
        <div className="flex flex-col gap-y-5">
            <div className="py-7 flex gap-x-5 border-b border-gray-border">
                {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                <div
                    className="w-[120px] h-auto aspect-square rounded-full overflow-hidden"
                    dangerouslySetInnerHTML={{
                        __html: iconAvatar(user?.custom_id).toString(),
                    }}
                />
                <div className="flex flex-col gap-y-3">
                    <div className="flex flex-col gap-y-1">
                        <div className="text-xl">{user?.name}</div>
                        <div className="text-base text-gray-main">
                            @{user?.custom_id}
                        </div>
                    </div>
                    {/* TODO: プロフィール編集ページへ遷移 */}
                    <TextButton onClick={() => {}}>
                        プロフィールを編集
                        <ChevronRight />
                    </TextButton>
                </div>
            </div>
            <div className="flex flex-col gap-y-6">
                <div>共有メンバー</div>
                {!!users && users.length > 1 ? (
                    <>
                        <div className="grid grid-cols-[repeat(auto-fill,_minmax(80px,_1fr))] gap-6">
                            {users
                                .filter(v => v.id !== user.id)
                                .map(user => (
                                    <div
                                        key={user.id}
                                        className="w-full max-w-[100px] mx-auto flex flex-col gap-y-1">
                                        {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                                        <div
                                            className="w-full h-auto aspect-square rounded-full overflow-hidden"
                                            dangerouslySetInnerHTML={{
                                                __html: iconAvatar(
                                                    user.custom_id,
                                                ).toString(),
                                            }}
                                        />
                                        <div className="text-xs text-center">
                                            {user.name}
                                        </div>
                                    </div>
                                ))}
                        </div>
                        <TextButton
                            onClick={() => {
                                fetchInvitationToken();
                                setIsOpenInviteDialog(true);
                            }}>
                            メンバーを追加
                            <ChevronRight />
                        </TextButton>
                    </>
                ) : (
                    <div className="flex flex-col gap-y-2">
                        <p>共有メンバーはまだいません。</p>
                        <TextButton
                            onClick={() => {
                                fetchInvitationToken();
                                setIsOpenInviteDialog(true);
                            }}>
                            メンバーを追加
                            <ChevronRight />
                        </TextButton>
                    </div>
                )}
            </div>
            {isOpenInviteDialog && (
                <Dialog
                    title="メンバーを招待"
                    onClose={() => setIsOpenInviteDialog(false)}>
                    <div className="flex flex-col gap-y-5">
                        <p>
                            QRコードやリンクを共有して、メンバーを招待しましょう
                        </p>
                        {invitationLink ? (
                            <div className="flex flex-col items-center gap-y-5">
                                <QRCodeSVG
                                    value={invitationLink}
                                    width="200"
                                    height="200"
                                    className="p-5"
                                />
                                <TextButton
                                    onClick={() =>
                                        copyToClipboard(invitationLink)
                                    }>
                                    招待リンクをコピー
                                    <Copy />
                                </TextButton>
                                <div>
                                    有効期限：
                                    {dayjs(tokenExpiresAt).format(
                                        'YYYY年MM月DD日 HH:mm',
                                    )}
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
                    </div>
                </Dialog>
            )}
        </div>
    );
};

export default Page;
