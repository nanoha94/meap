import { icons } from '@dicebear/collection';
import { createAvatar, Result } from '@dicebear/core';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';
import { useInvitations } from './useInvitations';
import { useAccountStore } from './stores';
import { JOIN_CHECK_DIALOG_CONFIGS } from '../constants/dialogs';
import { JoinErrorType } from '../constants/error';

export const useAccountHandlers = () => {
    const { isLoading, joinGroup } = useInvitations();
    const { openDialog, closeDialog } = useAccountStore();
    const router = useRouter();
    const pathname = usePathname();
    const searchParams = useSearchParams();

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

    /**
     * パスからトークンを削除
     */
    const removeTokenFromPath = () => {
        const newParams = new URLSearchParams(searchParams?.toString());
        newParams.delete('token');
        router.replace(`${pathname}?${newParams.toString()}`);
    };

    /**
     * グループに参加
     * @param isDelete データを削除してグループに参加するかどうか
     */
    const joinGroupWithToken = async (
        token: string,
        isDelete: boolean = false,
    ) => {
        const result = await joinGroup(token, isDelete);

        // データを削除せずグループに参加しようとした場合
        if (!isDelete) {
            // エラーの場合
            if (!result.success) {
                // エラーの種類に応じてアラートダイアログの内容をセット
                if (result.errorStatus === 409 && result.errorType) {
                    openDialog(
                        'deleteCheck',
                        JOIN_CHECK_DIALOG_CONFIGS[
                            result.errorType as keyof typeof JoinErrorType
                        ],
                    );
                }
                closeDialog('join');
            }
            // 成功した場合
            else {
                // ページをリロードしてAPIリクエストを再実行
                closeDialog('join');
                removeTokenFromPath();
                router.refresh();
            }
        }
        // データを削除してグループに参加しようとした場合
        else {
            // ページをリロードしてAPIリクエストを再実行
            closeDialog('deleteCheck');
            removeTokenFromPath();
            router.refresh();
        }
    };

    return { isLoading, iconAvatar, removeTokenFromPath, joinGroupWithToken };
};
