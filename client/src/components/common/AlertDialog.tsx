import Button from './Button';

interface Props {
    title: string;
    description: React.ReactNode;
    isOpen: boolean;
    onClose: () => void;
    actionButton: { text: string; onClick: () => void };
}

const AlertDialog = ({
    title,
    isOpen,
    onClose,
    actionButton,
    description,
}: Props) => {
    if (!isOpen) return null;

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
                    <div className="flex flex-col gap-y-7">
                        {description}
                        <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                            <Button
                                colorVariant="gray"
                                variant="outlined"
                                onClick={onClose}>
                                キャンセル
                            </Button>
                            <Button
                                onClick={actionButton.onClick}
                                colorVariant="alert">
                                {actionButton.text}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AlertDialog;
