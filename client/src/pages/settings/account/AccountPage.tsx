'use client';
import React from 'react';
import { ChevronRight } from 'lucide-react';

import { Header, Invitation, JoinGroup, ProfileEditForm, TextButton } from '@/components';
import { useDialog, useSnackbars } from '@/hooks';
import { useUserStore, iconAvatar, useAccountNavigation } from '@/models/user';
import { IInvitation } from '@/types';
import Image from 'next/image';

interface Props {
    invitationDetail?: IInvitation | null;
    errorMessage?: string;
}

const AccountPage = ({ invitationDetail, errorMessage }: Props) => {
    // store
    const loginUser = useUserStore(state => state.loginUser);
    const users = useUserStore(state => state.users);

    // hook
    const { addSnackbar } = useSnackbars();
    const { openDialog } = useDialog();
    const { removeTokenFromPath } = useAccountNavigation();

    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage, addSnackbar]);

    /**
     * 招待トークンがある場合、グループに参加するダイアログを表示
     * クライアントサイドでのみ実行するため、SSR では false / クライアントでは true となる
     */
    const subscribe = React.useCallback(() => () => {}, []);
    const isMounted = React.useSyncExternalStore(subscribe, () => true, () => false);

    React.useEffect(() => {
        // クライアントサイドでのみ実行（Suspenseのハイドレーション後に実行）
        if (!isMounted) return;

        if (invitationDetail) {
            openDialog({
                title: 'グループに参加',
                children: <JoinGroup invitationDetail={invitationDetail} />,
            }, removeTokenFromPath);
        }
    }, [invitationDetail, isMounted, openDialog, removeTokenFromPath]);

    return (
        <>
            <Header title="アカウント設定" hasBackButton={true} />
            <main className="p-5 pb-[60px] md:px-10  max-w-[1000px] mx-auto flex flex-col">
                <div className="pt-2 pb-7 mb-7 flex gap-x-5">
                    <div
                        className="w-[120px] h-auto aspect-square rounded-full overflow-hidden">
                        {loginUser?.avatar?.image ? (
                            <Image
                                src={loginUser.avatar.image.src}
                                alt="avatar"
                                width={loginUser.avatar.image.width}
                                height={loginUser.avatar.image.height}
                                className="w-full h-full object-cover"
                                priority
                            />
                        ) : (
                            <div
                                dangerouslySetInnerHTML={{
                                    __html: iconAvatar(
                                        loginUser?.avatar?.seed ?? '',
                                    ).toString(),
                                }}
                            />
                        )}
                    </div>
                    <div className="flex flex-col gap-y-3">
                        <div className="flex flex-col gap-y-1">
                            <div className="text-xl">{loginUser?.name}</div>
                        </div>
                        <TextButton onClick={() => {
                            openDialog({
                                title: 'プロフィール編集',
                                children: <ProfileEditForm />,
                            });
                        }}>
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
                                    .filter(
                                        v =>
                                            v.id !== loginUser?.id,
                                    )
                                    .map(user => (
                                        <div
                                            key={user.avatar.seed}
                                            className="w-full max-w-[100px] mx-auto flex flex-col gap-y-1">
                                            <div
                                                className="w-full h-auto aspect-square rounded-full overflow-hidden">
                                                {user?.avatar?.image ? (
                                                    <Image
                                                        src={user.avatar.image.src}
                                                        alt="avatar"
                                                        width={user.avatar.image.width}
                                                        height={user.avatar.image.height}
                                                        className="w-full h-full object-cover"
                                                    />
                                                ) : (
                                                    <div
                                                        dangerouslySetInnerHTML={{
                                                            __html: iconAvatar(
                                                                user?.avatar?.seed ?? '',
                                                            ).toString(),
                                                        }}
                                                    />
                                                )}</div>
                                            <div className="text-sm text-center">
                                                {user.name}
                                            </div>
                                        </div>
                                    ))}
                            </div>
                            <TextButton
                                onClick={() => {
                                    openDialog({
                                        title: 'メンバー招待',
                                        children: <Invitation />,
                                    });
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
                                    openDialog({
                                        title: 'メンバー招待',
                                        children: <Invitation />
                                    });
                                }}>
                                メンバーを招待
                                <ChevronRight />
                            </TextButton>
                        </div>
                    )}
                </div>
            </main>
        </>
    );
};

export default AccountPage;
