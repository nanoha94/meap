'use client';
import { icons } from '@dicebear/collection';
import { createAvatar, Result } from '@dicebear/core';
import { AlertDialog, TextButton } from '../../_components';
import { ChevronRight } from 'lucide-react';
import { useAuth } from '@/hooks';
import React from 'react';
import axios from '@/lib/axios';
import { IGetGroupUser } from '@/types/api';
import useSWR from 'swr';
import { InvitationDialog, JoinDialog } from './_components';
import { useSearchParams } from 'next/navigation';
import { Button } from '@/components';

const fetchGroupUsers = (path: string): Promise<IGetGroupUser[]> =>
    axios.get(path).then(res => res.data);

const Page = () => {
    const searchParams = useSearchParams();
    const token = searchParams.get('token');
    const { user } = useAuth();
    const { data: users, error } = useSWR('/api/group/users', fetchGroupUsers);

    const [isOpenInviteDialog, setIsOpenInviteDialog] =
        React.useState<boolean>(false);
    const [isOpenJoinDialog, setIsOpenJoinDialog] =
        React.useState<boolean>(!!token);
    const [isOpenAlertDialog, setIsOpenAlertDialog] =
        React.useState<boolean>(false);
    const [alertDialogMessage, setAlertDialogMessage] =
        React.useState<string>('');

    const iconAvatar = (id: string): Result =>
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

    const JoinGroupWithToken = async (isDelete: boolean) => {
        try {
            const res = await axios.post(`/api/group/users/join/${token}`, {
                isDelete,
            });

            if (res.data) {
                // TODO: スナックバーで表示
                console.log(res.data.message);
            }
        } catch (error) {
            if (error.response.status === 409) {
                setAlertDialogMessage(error.response.data.message);
                setIsOpenAlertDialog(true);
                setIsOpenJoinDialog(false);
                return;
            }

            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        }

        // ダイアログを閉じる
        setIsOpenJoinDialog(false);
        setIsOpenAlertDialog(false);

        // URLパラメータを更新
        const url = new URL(window.location.href);
        url.searchParams.delete('token');
        window.history.pushState({}, '', url);
    };

    React.useEffect(() => {
        if (error) {
            // TODO: スナックバーでエラー表示
            console.error(error?.response?.data?.message);
        }
    }, [error]);

    // セッションストレージにトークンがある場合は削除
    if (token) {
        if (sessionStorage.getItem('invitationToken')) {
            sessionStorage.removeItem('invitationToken');
        }
    }

    return (
        <div className="flex flex-col">
            <div className="pt-2 pb-7 mb-7 flex gap-x-5 border-b border-gray-border">
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
                                setIsOpenInviteDialog(true);
                            }}>
                            メンバーを招待
                            <ChevronRight />
                        </TextButton>
                    </>
                ) : (
                    <div className="flex flex-col gap-y-2">
                        <p>共有メンバーはまだいません。</p>
                        <TextButton
                            onClick={() => {
                                setIsOpenInviteDialog(true);
                            }}>
                            メンバーを招待
                            <ChevronRight />
                        </TextButton>
                    </div>
                )}
            </div>
            {/* 招待ダイアログ */}
            {isOpenInviteDialog && (
                <InvitationDialog
                    onClose={() => setIsOpenInviteDialog(false)}
                />
            )}
            {/* 参加ダイアログ */}
            {isOpenJoinDialog && (
                <JoinDialog
                    token={token}
                    iconAvatar={iconAvatar}
                    onClose={() => {
                        setIsOpenJoinDialog(false);
                    }}
                    JoinGroupWithToken={() => JoinGroupWithToken(false)}
                />
            )}
            {/* 削除確認ダイアログ */}
            {isOpenAlertDialog && (
                <AlertDialog
                    title="データ削除"
                    onClose={() => setIsOpenAlertDialog(false)}>
                    <div className="flex flex-col gap-y-7">
                        <p className="text-center">
                            {alertDialogMessage}
                            <br />
                            削除してグループに参加しますか？
                        </p>
                        <div className="mx-auto max-w-[320px] w-full flex gap-x-3">
                            <Button
                                colorVariant="gray"
                                variant="outlined"
                                onClick={() => setIsOpenAlertDialog(false)}>
                                キャンセル
                            </Button>
                            <Button
                                onClick={() => JoinGroupWithToken(true)}
                                colorVariant="alert">
                                削除して参加
                            </Button>
                        </div>
                    </div>
                </AlertDialog>
            )}
        </div>
    );
};

export default Page;
