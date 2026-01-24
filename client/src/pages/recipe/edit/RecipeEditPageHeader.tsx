'use client';
import React from 'react';
import { AlertDialog, Header, HeaderTextButton } from '@/components/common';
import StyledSelect from '@/components/common/StyledSelect';
import { useAccountStore } from '@/models/settings/hooks';
import { IRecipe, IUser } from '@/types/api';
import { Save, Trash } from 'lucide-react';
import { useRecipeApi } from '@/models/recipe/hooks';
import { ActionButton, AlertDialogData } from '@/types';
import { ALERT_DIALOG_STATE_DEFAULT, COLOR_VARIANT } from '@/constants';

interface Props {
    ownerUserId: string;
    users: IUser[];
    fetchRecipe?: IRecipe;
    onChangeOwnerUserId: (userId: string) => void;
    onClickSaveButton: () => void;
}

const RecipeEditPageHeader = ({ ownerUserId, users, fetchRecipe, onChangeOwnerUserId, onClickSaveButton }: Props) => {
    const { loginUser } = useAccountStore();
    const { deleteRecipe } = useRecipeApi();
    const [deleteCheckDialog, setDeleteCheckDialog] =
        React.useState<AlertDialogData>(ALERT_DIALOG_STATE_DEFAULT);

    /**
    * 削除確認ダイアログを閉じる
    */
    const closeDeleteCheckDialog = () => {
        setDeleteCheckDialog(ALERT_DIALOG_STATE_DEFAULT);
    };

    /**
     * 削除確認ダイアログを開く
     * @param config ダイアログの設定
     */
    const openDeleteCheckDialog = () => {
        if (!fetchRecipe) {
            return;
        }
        setDeleteCheckDialog({
            isOpen: true,
            config: {
                title: '削除',
                message: [`${name}を削除しますか？`],
                alertMessage: '',
                actionButtonText: '削除',
            },
            onCancel: closeDeleteCheckDialog,
            onAction: () => {
                closeDeleteCheckDialog();
                deleteRecipe(fetchRecipe.id, fetchRecipe.name);
            },
            isLoading: false,
        });
    };

    const actionButtons: ActionButton[] = fetchRecipe?.ownerUserId === loginUser?.id ? [
        // 削除できるのは、編集責任者のみ
        {
            label: '削除する',
            icon: <Trash size={20} strokeWidth={2} />,
            onClick: openDeleteCheckDialog,
            color: COLOR_VARIANT.ALERT,
        },
    ] : [];

    return (
        <>
            <Header
                hasBackButton={true}
                leftContent={
                    <div className="items-center gap-x-4 whitespace-nowrap w-[300px] hidden md:flex">
                        <span>編集責任者</span>
                        <StyledSelect
                            value={ownerUserId}
                            name="userId"
                            options={users}
                            isShowPlaceholder={false}
                            onChange={e => {
                                onChangeOwnerUserId(e.target.value);
                            }}
                        />
                    </div>
                }
                rightContent={
                    <>
                        <HeaderTextButton colorVariant={COLOR_VARIANT.SECONDARY}
                            onClick={onClickSaveButton}>
                            <Save size={20} strokeWidth={2} />
                            保存
                        </HeaderTextButton>
                        {/* TODO: 外部公開 */}
                        {/* <HeaderTextButton colorVariant="gray"
                        onClick={() => {
                            console.log('save');
                        }}>
                        <Earth size={20} strokeWidth={2} />
                        外部公開
                    </HeaderTextButton> */}
                    </>
                }
                actionButtons={actionButtons}
            /> <AlertDialog
                isOpen={deleteCheckDialog.isOpen}
                config={deleteCheckDialog.config}
                onCancel={deleteCheckDialog.onCancel}
                onAction={deleteCheckDialog.onAction}
                isLoading={deleteCheckDialog.isLoading}
            /></>
    );
};

export default RecipeEditPageHeader;
