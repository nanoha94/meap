import { Dialog } from '@/components/common';
import { useRecipeStore } from '../../hooks/recipeStores';
import EditForm from './EditForm';
import { RECIPE_SETTING_DIALOG_CONFIGS } from '../../constants';
import { DIALOG_NAME } from '@/constants';

const RecipeCategorySettingDialog = () => {
    const dialogName = DIALOG_NAME.RECIPE_CATEGORY_SETTING;
    const { dialogs, closeDialog } = useRecipeStore();
    const { isOpen } = dialogs[dialogName];
    const dialogConfig = RECIPE_SETTING_DIALOG_CONFIGS[dialogName];

    return (
        <Dialog
            title={dialogConfig.title}
            isOpen={isOpen}
            onClose={() => closeDialog(dialogName)}>
            <EditForm onClose={() => closeDialog(dialogName)} />
        </Dialog>
    );
};

export default RecipeCategorySettingDialog;
