import { Dialog } from '@/components/common';
import EditForm from './EditForm';

interface Props {
    onClose: () => void;
}

const ShoppingCategorySettingDialog: React.FC<Props> = ({ onClose }) => {
    return (
        <Dialog title="買い物カテゴリ―設定" onClose={onClose}>
            <div className="w-full flex flex-col items-center gap-y-10">
                <EditForm onBack={onClose} />
            </div>
        </Dialog>
    );
};

export default ShoppingCategorySettingDialog;
