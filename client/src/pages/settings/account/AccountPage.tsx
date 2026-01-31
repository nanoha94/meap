'use client';
import React from 'react';
import { ChevronRight } from 'lucide-react';

import { Header, Invitation, JoinGroup, TextButton } from '@/components';
import { useDialog, useSnackbars } from '@/hooks';
import { useAccountHandlers, useAccountStore } from '@/models/settings';
import { IInvitation } from '@/types';

interface Props {
    invitationDetail?: IInvitation | null;
    errorMessage?: string;
}

const AccountPage = ({ invitationDetail, errorMessage }: Props) => {
    const { addSnackbar } = useSnackbars();
    const { loginUser, users } = useAccountStore();
    const { iconAvatar } = useAccountHandlers();
    const { openDialog } = useDialog();



    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage]);

    /**
     * 招待トークンがある場合、グループに参加するダイアログを表示
     * クライアントサイドでのみ実行するため、マウント済みかどうかをチェック
     */
    const [isMounted, setIsMounted] = React.useState(false);

    React.useEffect(() => {
        setIsMounted(true);
    }, []);

    React.useEffect(() => {
        // クライアントサイドでのみ実行（Suspenseのハイドレーション後に実行）
        if (!isMounted) return;

        if (invitationDetail) {
            openDialog({
                title: 'グループに参加',
                children: () => (
                    <JoinGroup invitationDetail={invitationDetail} />
                ),
            });
        }
    }, [invitationDetail, isMounted]);

    return (
        <>
            <Header title="アカウント設定" hasBackButton={true} />
            <main className="p-5 pb-[60px] md:px-10  max-w-[1000px] mx-auto flex flex-col">
                <div className="pt-2 pb-7 mb-7 flex gap-x-5">
                    {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                    <div
                        className="w-[120px] h-auto aspect-square rounded-full overflow-hidden"
                        dangerouslySetInnerHTML={{
                            __html: iconAvatar(
                                loginUser?.avatar_seed ?? '',
                            ).toString(),
                        }}
                    />
                    <div className="flex flex-col gap-y-3">
                        <div className="flex flex-col gap-y-1">
                            <div className="text-xl">{loginUser?.name}</div>
                        </div>
                        {/* TODO: プロフィール編集ページへ遷移 */}
                        <TextButton onClick={() => { }}>
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
                                            v.avatar.seed !==
                                            loginUser?.avatar_seed,
                                    )
                                    .map(user => (
                                        <div
                                            key={user.avatar.seed}
                                            className="w-full max-w-[100px] mx-auto flex flex-col gap-y-1">
                                            {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                                            <div
                                                className="w-full h-auto aspect-square rounded-full overflow-hidden"
                                                dangerouslySetInnerHTML={{
                                                    __html: iconAvatar(
                                                        user.avatar.seed ?? '',
                                                    ).toString(),
                                                }}
                                            />
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
                                        children: () => <Invitation />,
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
                                        children: () => <Invitation />
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
