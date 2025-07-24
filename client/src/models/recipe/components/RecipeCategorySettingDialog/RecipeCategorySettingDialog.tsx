import { Dialog } from '@/components/common';
import { useRecipeStore } from '../../hooks/recipeStores';
import EditForm from './EditForm';

const RecipeCategorySettingDialog = () => {
    const { dialogs, closeDialog } = useRecipeStore();
    const { isOpen } = dialogs.categorySetting;

    return (
        <Dialog
            title="料理カテゴリ―設定"
            isOpen={isOpen}
            onClose={() => closeDialog('categorySetting')}>
            <EditForm onClose={() => closeDialog('categorySetting')} />
        </Dialog>
    );
};

export default RecipeCategorySettingDialog;
