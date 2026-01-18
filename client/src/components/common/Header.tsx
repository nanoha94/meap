interface Props {
    title?: string;
    leftContent?: React.ReactNode;
    rightContent?: React.ReactNode;
}

const Header = ({ title, leftContent, rightContent }: Props) => {
    return (
        <header
            className="bg-white"
            style={{ boxShadow: 'inset 0 -1px 3px 0 rgba(0, 0, 0, 10%)' }}>
            <div className="py-3 px-4 max-w-[1000px] mx-auto min-h-[60px] flex items-center justify-between gap-x-10 sm:px-6 lg:px-10">
                <div className="flex items-center gap-x-4">
                    <h2 className="font-semibold text-xl text-gray-800">
                        {title}
                    </h2>
                    {leftContent}
                </div>
                {rightContent}
            </div>
        </header>
    );
};

export default Header;
