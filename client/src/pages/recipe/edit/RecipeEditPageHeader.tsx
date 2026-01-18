'use client';

import { Header } from '@/components/common';
import StyledSelect from '@/components/common/StyledSelect';
import { IUser } from '@/types/api';
import { useState } from 'react';

interface RecipeEditPageHeaderProps {
    initialUserId?: string;
    users: IUser[];
}

const RecipeEditPageHeader = ({ initialUserId, users }: RecipeEditPageHeaderProps) => {
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
        />
    );
};

export default RecipeEditPageHeader;
