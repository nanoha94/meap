'use client';
import React from 'react';
import dayjs from 'dayjs';
import { Copy, LoaderCircle, RotateCw } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';

import { TextButton } from '@/components';
import { colors } from '@/constants';
import { useDialog, useTextCopy } from '@/hooks';
import { useInvitationApi } from '@/models/user';

const Invitation: React.FC = () => {
    const { closeDialog } = useDialog();
    const { isTextCopied, copyToClipboard } = useTextCopy();
    const { isFetching, invitationLink, tokenExpiresAt, fetchInvitationToken } =
        useInvitationApi();

    // ダイアログが開いたら招待リンクを取得
    // 取得に失敗したらダイアログを閉じる
    React.useEffect(() => {
        fetchInvitationToken(() => {
            closeDialog();
        });
    }, []);

    return (
        <div className="flex flex-col gap-y-5">
            <p>QRコードやリンクを共有して、メンバーを招待しましょう</p>
            {isFetching || !invitationLink ? (
                <div className="py-5">
                    <LoaderCircle
                        size={40}
                        color={colors.primary.main}
                        className="animate-spin mx-auto"
                    />
                </div>
            ) : (
                <div className="flex flex-col items-center gap-y-5">
                    <div className="flex flex-col items-center">
                        <button
                            onClick={() =>
                                fetchInvitationToken(() => closeDialog())
                            }
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
                            {isTextCopied && (
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
            )}
        </div>
    );
};

export default Invitation;
