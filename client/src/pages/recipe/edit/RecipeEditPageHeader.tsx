'use client';
import React from 'react';
import { Header, HeaderTextButton } from '@/components';
import { StyledSelect } from '@/components/form-fields';
import { useAccountStore } from '@/models/settings/hooks';
import { IRecipe } from '@/types/api';
import { Save, Trash2 } from 'lucide-react';
import { useRecipeApi } from '@/models/recipe/hooks';
import { ActionButton } from '@/types';
import { COLOR_VARIANT } from '@/constants';
import { useAlertDialog } from '@/hooks/useAlertDialog';
import { RECIPE_ALERT_DIALOG_CONFIGS } from '@/models/recipe/constants';

interface Props {
    ownerUserId: string;
    fetchRecipe?: IRecipe;
    onChangeOwnerUserId: (userId: string) => void;
    onClickSaveButton: () => void;
}

const RecipeEditPageHeader = ({ ownerUserId, fetchRecipe, onChangeOwnerUserId, onClickSaveButton }: Props) => {
    const { loginUser, users } = useAccountStore();
    const { deleteRecipe } = useRecipeApi();
    const { openAlertDialog } = useAlertDialog();

    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtons: ActionButton[] = fetchRecipe?.ownerUserId === loginUser?.id ? [
        // 削除できるのは、編集責任者のみ
        {
            label: '削除する',
            icon: <Trash2 size={20} strokeWidth={2} />,
            onClick: () => openAlertDialog(
                RECIPE_ALERT_DIALOG_CONFIGS.deleteItem(fetchRecipe.name),
                () => {
                    deleteRecipe(fetchRecipe.id, fetchRecipe.name);
                }
            ),
            color: COLOR_VARIANT.ALERT,
        },
    ] : [];

    return (
        <>
            <Header
                maxWidth="1200px"
                hasBackButton={true}
                leftContent={
                    <div className="items-center gap-x-4 whitespace-nowrap w-[300px] hidden md:flex">
                        <span>編集責任者</span>
                        <StyledSelect
                            value={ownerUserId || loginUser?.id}
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
            />
        </>
    );
};

export default RecipeEditPageHeader;
