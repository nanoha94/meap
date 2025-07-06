'use client';
import React from 'react';
import { Dialog, TextButton } from '@/components/common';
import { colors } from '@/constants/colors';
import dayjs from 'dayjs';
import { Copy, LoaderCircle, RotateCw } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useAccountStore } from '../../hooks';
import { useInvitations } from '../../hooks/useInvitations';

const InvitationDialog = () => {
    const { dialogs, closeDialog } = useAccountStore();
    const { isOpen } = dialogs.invitation;
    const [isCopied, setIsCopied] = React.useState(false);
    const { isLoading, invitationLink, tokenExpiresAt, fetchInvitationToken } =
        useInvitations();

    const copyToClipboard = async (link: string) => {
        try {
            await navigator.clipboard.writeText(link);
            setIsCopied(true);
        } catch (err) {
            console.error(err);
        }
    };

    const fetchInvitation = async () => {
        const result = await fetchInvitationToken();
        if (!result.success) {
            // エラーの場合ダイアログを閉じる
            closeDialog('invitation');
        }
    };

    React.useEffect(() => {
        if (isOpen) {
            fetchInvitation();
        }
    }, [isOpen]);

    React.useEffect(() => {
        if (isCopied) {
            setTimeout(() => {
                setIsCopied(false);
            }, 3000);
        }
    }, [isCopied]);

    return (
        <Dialog
            title="メンバー招待"
            isOpen={isOpen}
            onClose={() => closeDialog('invitation')}>
            <div className="flex flex-col gap-y-5">
                <p>QRコードやリンクを共有して、メンバーを招待しましょう</p>
                {!isLoading && invitationLink ? (
                    <div className="flex flex-col items-center gap-y-5">
                        <div className="flex flex-col items-center">
                            <button
                                onClick={fetchInvitationToken}
                                className="p-2 w-fit bg-gray-background rounded-full transition-colors hover:bg-gray-light">
                                <RotateCw
                                    size={24}
                                    strokeWidth={2.5}
                                    color={colors.primary.main}
                                />
                            </button>
                            <QRCodeSVG
                                value={invitationLink}
                                width="200"
                                height="200"
                                className="p-5"
                            />
                        </div>
                        <div className="flex flex-col items-center gap-y-2">
                            <TextButton
                                onClick={() => copyToClipboard(invitationLink)}>
                                招待リンクをコピー
                                <Copy size={20} />
                            </TextButton>
                            <div className="min-h-[1.5rem]">
                                {isCopied && (
                                    <p className="text-alert-main">
                                        招待リンクをコピーしました
                                    </p>
                                )}
                            </div>
                        </div>
                        {tokenExpiresAt && (
                            <div>
                                有効期限：
                                {dayjs(tokenExpiresAt).format(
                                    'YYYY年MM月DD日 HH:mm',
                                )}
                            </div>
                        )}
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
    );
};

export default InvitationDialog;
