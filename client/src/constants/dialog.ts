import { AlertDialogConfig, AlertDialogData } from '@/types/dialog';

export const ALERT_DIALOG_CONFIG_DEFAULT: AlertDialogConfig = {
    title: '',
    message: [],
    alertMessage: '',
    actionButtonText: '',
};
export const ALERT_DIALOG_STATE_DEFAULT: AlertDialogData = {
    isOpen: false,
    config: ALERT_DIALOG_CONFIG_DEFAULT,
    onCancel: () => {},
    onAction: () => {},
    isLoading: false,
};
