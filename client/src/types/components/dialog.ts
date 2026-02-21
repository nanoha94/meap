export type AlertDialogConfig = {
    title: string;
    message: string[];
    alertMessage: string;
    actionButtonText: string;
};

export type AlertDialogData = {
    isOpen: boolean;
    config: AlertDialogConfig;
    onCancel: () => void;
    onAction: () => void;
};

export type DialogConfig = {
    title: string;
    customButton?: React.ReactNode;
    maxWidth?: number;
    children: () => React.ReactNode;
};

export type DialogData = {
    isOpen: boolean;
    config: DialogConfig;
    onClose: () => void;
};
