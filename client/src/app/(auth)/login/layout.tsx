import { createPageMetadata, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.LOGIN);

interface Props {
    children: React.ReactNode;
}

const LoginLayout = ({ children }: Props) => {
    return children;
};

export default LoginLayout;
