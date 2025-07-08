'use client';
import { AlertDialog } from '@/components/common';
import { useAccountHandlers } from '../../hooks/useAccountHandlers';
import { useAccountStore } from '../../hooks';

interface Props {
    token: string;
}

const DeleteCheckDialog = ({ token }: Props) => {
    const { dialogs, closeDialog } = useAccountStore();
    const { isOpen, payload } = dialogs.deleteCheck;
    const { isLoading, removeTokenFromPath, joinGroupWithToken } =
        useAccountHandlers();

    const handleClose = () => {
        closeDialog('deleteCheck');
        removeTokenFromPath();
    };

    return (
        <AlertDialog
            title="データ削除"
            description={
                <div className="flex flex-col gap-y-4">
                    <p className="text-center whitespace-pre-wrap">
                        {payload?.message}
                    </p>
                    <span className="text-center text-alert-main">
                        {payload?.alertMessage}
                    </span>
                </div>
            }
            isLoading={isLoading}
            isOpen={isOpen}
            onClose={handleClose}
            actionButton={{
                text: payload?.buttonText ?? '削除して参加',
                onClick: () => joinGroupWithToken(token, true),
            }}
        />
    );
};

export default DeleteCheckDialog;
