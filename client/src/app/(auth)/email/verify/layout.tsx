import { createPageMetadata, LINK_TO, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.EMAIL_VERIFY, {
    path: LINK_TO.EMAIL_VERIFY,
});

interface Props {
    children: React.ReactNode;
}

const EmailVerifyLayout = ({ children }: Props) => {
    return children;
};

export default EmailVerifyLayout;
