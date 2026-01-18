'use client';

import { Header, HeaderTextButton } from '@/components/common';
import StyledSelect from '@/components/common/StyledSelect';
import { IUser } from '@/types/api';
import { Save } from 'lucide-react';
import { useState } from 'react';

interface Props {
    initialUserId?: string;
    users: IUser[];
    onClickSaveButton: () => void;
}

const RecipeEditPageHeader = ({ initialUserId, users, onClickSaveButton }: Props) => {
    const [userId, setUserId] = useState(initialUserId ?? '');

    return (
        <Header
            leftContent={
                <div className="flex items-center gap-x-4 whitespace-nowrap w-[300px]">
                    <span>編集責任者</span>
                    <StyledSelect
                        value={userId}
                        name="userId"
                        options={users}
                        isShowPlaceholder={false}
                        onChange={e => {
                            setUserId(e.target.value);
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
