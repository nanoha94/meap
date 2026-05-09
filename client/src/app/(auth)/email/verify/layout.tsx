import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'メールアドレスの確認',
};

interface Props {
    children: React.ReactNode;
}

const EmailVerifyLayout = ({ children }: Props) => {
    return children;
};

export default EmailVerifyLayout;
