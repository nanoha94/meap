import { createPageMetadata, LINK_TO, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.LOGIN, {
    path: LINK_TO.LOGIN,
});

interface Props {
    children: React.ReactNode;
}

const LoginLayout = ({ children }: Props) => {
    return children;
};

export default LoginLayout;
