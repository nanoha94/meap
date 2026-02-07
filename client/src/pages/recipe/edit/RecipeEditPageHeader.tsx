'use client';
import React from 'react';
import { Save, Trash2 } from 'lucide-react';

import { Header, HeaderTextButton, StyledSelect } from '@/components';
import { BUTTON_TYPE, COLOR_VARIANT } from '@/constants';
import { useAlertDialog } from '@/hooks';
import {
    RECIPE_ALERT_DIALOG_CONFIGS,
    useRecipeApi,
} from '@/models/recipe';
import { useAccountStore } from '@/models/settings';
import { ActionButton, IRecipe } from '@/types';

interface Props {
    ownerUserId: string;
    fetchRecipe?: IRecipe;
    onChangeOwnerUserId: (userId: string) => void;
}

const RecipeEditPageHeader = ({ ownerUserId, fetchRecipe, onChangeOwnerUserId }: Props) => {
    const { loginUser, users } = useAccountStore();
    const { deleteRecipe } = useRecipeApi();
    const { openAlertDialog } = useAlertDialog();

    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtonConfigs: ActionButton[] = fetchRecipe?.ownerUserId === loginUser?.id ? [
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
                        <HeaderTextButton type={BUTTON_TYPE.SUBMIT} form="recipe-edit-form" colorVariant={COLOR_VARIANT.SECONDARY}>
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
                actionButtons={actionButtonConfigs}
            />
        </>
    );
};

export default RecipeEditPageHeader;
