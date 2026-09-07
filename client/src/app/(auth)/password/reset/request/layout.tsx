import { createPageMetadata, LINK_TO, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.PASSWORD_RESET, {
    path: LINK_TO.PASSWORD_RESET_REQUEST,
});

interface Props {
    children: React.ReactNode;
}

const PasswordResetRequestLayout = ({ children }: Props) => {
    return children;
};

export default PasswordResetRequestLayout;
