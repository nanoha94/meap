import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'パスワード再設定',
};

interface Props {
    children: React.ReactNode;
}

const PasswordResetTokenLayout = ({ children }: Props) => {
    return children;
};

export default PasswordResetTokenLayout;
