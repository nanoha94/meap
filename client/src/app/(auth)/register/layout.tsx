import { createPageMetadata, LINK_TO, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.REGISTER, {
    path: LINK_TO.REGISTER,
});

interface Props {
    children: React.ReactNode;
}

const RegisterLayout = ({ children }: Props) => {
    return children;
};

export default RegisterLayout;
