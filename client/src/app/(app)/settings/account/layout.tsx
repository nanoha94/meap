import { Header } from '../../_components';

interface Props {
    children: React.ReactNode;
}

const Layout = ({ children }: Props) => {
    return (
        <>
            <Header title="アカウント設定" />
            <main className="p-5">{children}</main>
        </>
    );
};

export default Layout;
