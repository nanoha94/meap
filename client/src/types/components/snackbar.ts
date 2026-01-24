export interface Snackbar {
    id: string;
    message: string;
    type: 'success' | 'error';
    isOpen: boolean;
}
