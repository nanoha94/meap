import { createPageMetadata, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.PASSWORD_RESET);

interface Props {
    children: React.ReactNode;
}

const PasswordResetRequestLayout = ({ children }: Props) => {
    return children;
};

export default PasswordResetRequestLayout;
