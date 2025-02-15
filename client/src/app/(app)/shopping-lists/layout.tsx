import { Header } from '../_components';

interface Props {
    children: React.ReactNode;
}

const Layout = ({ children }: Props) => {
    return (
        <>
            <Header title="買い物リスト" />
            <main className="p-5">{children}</main>
        </>
    );
};

export default Layout;
