import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'ログイン',
};

interface Props {
    children: React.ReactNode;
}

const LoginLayout = ({ children }: Props) => {
    return children;
};

export default LoginLayout;
