'use client';
import { TextButton } from '@/components/common';
import { InvitationDialog, JoinDialog } from '@/models/settings/components';
import { useAccountStore } from '@/models/settings/hooks';
import { ChevronRight } from 'lucide-react';
import { IGetGroupUserResponse } from '@/types/api';
import { useAccountHandlers } from '@/models/settings/hooks/useAccountHandlers';

interface Props {
    users: IGetGroupUserResponse['data'];
    token: string;
}

const AccountTop = ({ users, token }: Props) => {
    const { openDialog, loginUser } = useAccountStore();
    const { iconAvatar } = useAccountHandlers();

    return (
        <div className="p-5 flex flex-col">
            <div className="pt-2 pb-7 mb-7 flex gap-x-5">
                {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                <div
                    className="w-[120px] h-auto aspect-square rounded-full overflow-hidden"
                    dangerouslySetInnerHTML={{
                        __html: iconAvatar(loginUser?.id ?? '').toString(),
                    }}
                />
                <div className="flex flex-col gap-y-3">
                    <div className="flex flex-col gap-y-1">
                        <div className="text-xl">{loginUser?.name}</div>
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
                                .filter(v => v.id !== loginUser?.id)
                                .map(user => (
                                    <div
                                        key={user.id}
                                        className="w-full max-w-[100px] mx-auto flex flex-col gap-y-1">
                                        {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                                        <div
                                            className="w-full h-auto aspect-square rounded-full overflow-hidden"
                                            dangerouslySetInnerHTML={{
                                                __html: iconAvatar(
                                                    user.id,
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
            {/* 参加ダイアログ */}
            <JoinDialog token={token ?? ''} />
        </div>
    );
};

export default AccountTop;
