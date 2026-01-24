export type AlertDialogConfig = {
    title: string;
    message: string[];
    alertMessage: string;
    actionButtonText: string;
};

export type AlertDialogData = {
    isOpen: boolean;
    isLoading: boolean;
    config: AlertDialogConfig;
    onCancel: () => void;
    onAction: () => void;
};
