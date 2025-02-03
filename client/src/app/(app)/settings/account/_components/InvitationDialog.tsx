import { Dialog, TextButton } from '@/app/(app)/_components';
import { colors } from '@/constants/colors';
import axios from '@/lib/axios';
import dayjs from 'dayjs';
import { Copy, LoaderCircle, RotateCw } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import React, { useEffect } from 'react';

interface Props {
    onClose: () => void;
}

const copyToClipboard = async (link: string) => {
    try {
        await navigator.clipboard.writeText(link);
    } catch (err) {
        console.error(err);
    }
};

const InvitationDialog = ({ onClose }: Props) => {
    const [invitationLink, setInvitationLink] = React.useState<string | null>(
        null,
    );
    const [tokenExpiresAt, setTokenExpiresAt] = React.useState<string | null>(
        null,
    );

    const fetchInvitationToken = async () => {
        setInvitationLink(null);
        try {
            const res = await axios.get('/api/invitation');
            if (res.data) {
                setInvitationLink(
                    `${process.env.NEXT_PUBLIC_FRONT_URL}/settings/account?token=${res.data.token}`,
                );
                setTokenExpiresAt(res.data.expires_at);
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        }
    };

    useEffect(() => {
        fetchInvitationToken();
    }, []);

    return (
        <Dialog title="メンバー招待" onClose={onClose}>
            <div className="flex flex-col gap-y-5">
                <p>QRコードやリンクを共有して、メンバーを招待しましょう</p>
                {invitationLink ? (
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
                        <TextButton
                            onClick={() => copyToClipboard(invitationLink)}>
                            招待リンクをコピー
                            <Copy size={20} />
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
    );
};

export default InvitationDialog;
