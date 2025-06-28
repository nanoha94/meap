import { Dialog } from '@/components/common';
import EditForm from './EditForm';
import { useShoppingDialogs } from '../../hooks';

const ShoppingCategorySettingDialog = () => {
    const { dialogs, closeDialog } = useShoppingDialogs();

    const handleClose = () => {
        closeDialog('itemSetting');
    };

    return (
        <Dialog
            title="買い物カテゴリ―設定"
            isOpen={dialogs.itemSetting}
            onClose={handleClose}>
            <EditForm onBack={handleClose} />
        </Dialog>
    );
};

export default ShoppingCategorySettingDialog;
