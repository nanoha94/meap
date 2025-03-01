import { Header } from '../_components';

interface Props {
    children: React.ReactNode;
}

const Layout = ({ children }: Props) => {
    return (
        <>
            <Header title="買い物リスト" />
            <div className="p-5">{children}</div>
        </>
    );
};

export default Layout;
