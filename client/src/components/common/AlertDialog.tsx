interface Props {
    title: string;
    onClose: () => void;
    children: React.ReactNode;
}

const AlertDialog = ({ title, onClose, children }: Props) => {
    return (
        <div
            onClick={onClose}
            className="fixed z-50 top-0 left-0 w-full h-screen bg-black/50">
            <div
                onClick={e => e.stopPropagation()}
                className="absolute top-10 left-1/2 -translate-x-1/2 max-w-[500px] w-[calc(100%-40px)] bg-white rounded-xl">
                <div className="px-5 py-12">
                    <div className="mb-7 px-5 py-2 w-full text-2xl font-bold text-center">
                        {title}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
};

export default AlertDialog;
