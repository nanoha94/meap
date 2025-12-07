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
    isLoading: boolean;
};
