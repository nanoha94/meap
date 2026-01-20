'use client';

import { Header, HeaderTextButton } from '@/components/common';
import StyledSelect from '@/components/common/StyledSelect';
import { IUser } from '@/types/api';
import { Save } from 'lucide-react';

interface Props {
    ownerUserId: string;
    users: IUser[];
    onChangeOwnerUserId: (userId: string) => void;
    onClickSaveButton: () => void;
}

const RecipeEditPageHeader = ({ ownerUserId, users, onChangeOwnerUserId, onClickSaveButton }: Props) => {
    return (
        <Header
            leftContent={
                <div className="flex items-center gap-x-4 whitespace-nowrap w-[300px]">
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
                </div>
            }
        />
    );
};

export default RecipeEditPageHeader;
