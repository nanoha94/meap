import type { Metadata } from 'next';

import { createPageMetadata, METADATA } from '@/constants';

interface Props {
    children: React.ReactNode;
    params: Promise<{ token: string }>;
}

export const generateMetadata = async ({
    params,
}: Props): Promise<Metadata> => {
    const { token } = await params;

    return createPageMetadata(METADATA.PAGE.PASSWORD_RESET, {
        path: `/password/reset/${token}`,
    });
};

const PasswordResetTokenLayout = ({ children }: Props) => {
    return children;
};

export default PasswordResetTokenLayout;
