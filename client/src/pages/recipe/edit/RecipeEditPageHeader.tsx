'use client';
import React from 'react';
import { Header, HeaderTextButton } from '@/components/common';
import StyledSelect from '@/components/common/StyledSelect';
import { HeaderDeleteButton } from '@/models/recipe/components';
import { useAccountStore } from '@/models/settings/hooks';
import { IRecipe, IUser } from '@/types/api';
import { Save } from 'lucide-react';

interface Props {
    ownerUserId: string;
    users: IUser[];
    fetchRecipe?: IRecipe;
    onChangeOwnerUserId: (userId: string) => void;
    onClickSaveButton: () => void;
}

const RecipeEditPageHeader = ({ ownerUserId, users, fetchRecipe, onChangeOwnerUserId, onClickSaveButton }: Props) => {
    const { loginUser } = useAccountStore();

    return (
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
                <div className='flex items-center gap-x-4'>
                    <HeaderTextButton colorVariant="secondary"
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
                    {/* 編集責任者の場合のみ削除ボタンを表示 */}
                    {fetchRecipe?.ownerUserId === loginUser?.id && <HeaderDeleteButton
                        id={fetchRecipe?.id ?? ''}
                        name={fetchRecipe?.name ?? ''}
                    />}
                </div>
            }
        />
    );
};

export default RecipeEditPageHeader;
