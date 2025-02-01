import { Header } from '../../_components';

interface Props {
    children: React.ReactNode;
}

const Layout = ({ children }: Props) => {
    return (
        <>
            <Header title="Plan" />
            <main className="px-5">{children}</main>
        </>
    );
};

export default Layout;
