'use client';

import { Header } from '@/components/common';
import StyledSelect from '@/components/common/StyledSelect';
import { IUser } from '@/types/api';
import { useState } from 'react';

interface EditHeaderProps {
    initialUserId?: string;
    users: IUser[];
}

const EditHeader = ({ initialUserId, users }: EditHeaderProps) => {
    const [userId, setUserId] = useState(initialUserId ?? '');

    return (
        <Header            
            leftContent={
                <div className='w-[300px] flex items-center gap-x-4 whitespace-nowrap'><span>編集責任者</span><StyledSelect
                    value={userId}
                    name="userId"
                    options={users}
                    isShowPlaceholder={false}
                    onChange={e => {
                        setUserId(e.target.value);
                    }}
                /></div>
            }
        />
    );
};

export default EditHeader;
