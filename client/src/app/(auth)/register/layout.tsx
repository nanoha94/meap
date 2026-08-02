import { createPageMetadata, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.REGISTER);

interface Props {
    children: React.ReactNode;
}

const RegisterLayout = ({ children }: Props) => {
    return children;
};

export default RegisterLayout;
