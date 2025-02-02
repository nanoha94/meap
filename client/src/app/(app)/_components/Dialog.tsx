import { colors } from '@/constants/colors';
import { X } from 'lucide-react';

interface Props {
    title: string;
    onClose: () => void;
    children: React.ReactNode;
}

const Dialog = ({ title, onClose, children }: Props) => {
    return (
        <div className="fixed z-50 top-0 left-0 w-full h-screen bg-black/50">
            <div className="absolute top-10 left-1/2 -translate-x-1/2 max-w-[500px] w-[calc(100%-40px)] bg-white rounded-xl">
                <div className="px-5 py-2 flex justify-between items-center gap-x-5 text-2xl font-bold border-b border-gray-border">
                    {title}
                    <button
                        onClick={onClose}
                        className="p-1 appearance-none rounded-full transition-colors hover:bg-gray-light">
                        <X size={32} color={colors.black} />
                    </button>
                </div>
                <div className="px-5 pt-5 pb-8">{children}</div>
            </div>
        </div>
    );
};

export default Dialog;
