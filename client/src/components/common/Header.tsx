interface Props {
    title: string;
    children?: React.ReactNode;
}

const Header = ({ title, children }: Props) => {
    return (
        <header className="bg-white shadow">
            <div className="max-w-7xl mx-auto py-6 px-4 flex items-center gap-x-10 sm:px-6 lg:px-8">
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    {title}
                </h2>
                {children}
            </div>
        </header>
    );
};

export default Header;
