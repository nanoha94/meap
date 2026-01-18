'use client';
import React from 'react';
import { Header, TextButton } from '@/components/common';
import { IUser, IInvitation } from '@/types/api';
import { useSnackbars } from '@/hooks/useSnackbars';
import { InvitationDialog, JoinDialog } from '@/models/settings/components';
import { useAccountHandlers, useAccountStore } from '@/models/settings/hooks';
import { ChevronRight } from 'lucide-react';

interface Props {
    users: IUser[];
    invitationDetail?: IInvitation | null;
    errorMessage?: string;
}

const AccountPage = ({ users, invitationDetail, errorMessage }: Props) => {
    const { addSnackbar } = useSnackbars();
    const { openDialog, loginUser, setUsers } = useAccountStore();
    const { iconAvatar } = useAccountHandlers();

    /**
     * ユーザー一覧を設定
     */
    React.useEffect(() => {
        setUsers(users);
    }, [users]);

    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage]);

    return (
        <>
            <Header title="アカウント設定" />
            <main>
                <div className="p-5 flex flex-col">
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
                                        openDialog('invitation', undefined);
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
                                        openDialog('invitation', undefined);
                                    }}>
                                    メンバーを招待
                                    <ChevronRight />
                                </TextButton>
                            </div>
                        )}
                    </div>
                    {/* 招待ダイアログ */}
                    <InvitationDialog />
                </div>
                {invitationDetail && (
                    <JoinDialog invitationDetail={invitationDetail} />
                )}
            </main>
        </>
    );
};

export default AccountPage;
