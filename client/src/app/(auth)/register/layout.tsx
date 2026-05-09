import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'アカウント登録',
};

interface Props {
    children: React.ReactNode;
}

const RegisterLayout = ({ children }: Props) => {
    return children;
};

export default RegisterLayout;
