'use client';
import HeaderTextButton from '@/components/common/HeaderTextButtons/HeaderTextButton';
import { Trash } from 'lucide-react';
import React from 'react';

const HeaderButton = () => {
    return (
        <HeaderTextButton colorVariant="alert" onClick={() => {}}>
            <Trash size={20} strokeWidth={2} />
            削除
        </HeaderTextButton>
    );
};

export default HeaderButton;
