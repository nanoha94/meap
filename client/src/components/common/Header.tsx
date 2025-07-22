interface Props {
    title: string;
    textButtons?: {
        label: string;
        onClick: () => void;
    }[];
    children?: React.ReactNode;
}

const Header = ({ title, children }: Props) => {
    return (
        <header className="bg-white shadow">
            <div className="py-6 px-4 flex items-center justify-between gap-x-10 sm:px-6 lg:px-10">
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    {title}
                </h2>
                {children}
            </div>
        </header>
    );
};

export default Header;
