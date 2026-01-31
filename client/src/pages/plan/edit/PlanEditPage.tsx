"use client";
import React from 'react';
import { Save, Trash2 } from 'lucide-react';
import 'react-datepicker/dist/react-datepicker.css';

import { Header, HeaderTextButton, StyledDatePicker } from '@/components';
import { COLOR_VARIANT } from '@/constants';
import { PlanEditCard } from '@/models/meal';
import { ActionButton } from '@/types';


const PlanEditPage = () => {
    const [selectedDate, setSelectedDate] = React.useState(new Date());

    /**
         * メニューボタン押下時に開くアクションボタン設定
         */
    const actionButtons: ActionButton[] = [
        {
            label: '削除する',
            icon: <Trash2 size={20} strokeWidth={2} />,
            onClick: () => {
                // TODO: 削除ダイアログ実装
            },
            color: COLOR_VARIANT.ALERT,
        },
    ];
    return (
        <>
            <Header hasBackButton={true} leftContent={
                <div className="items-center gap-x-4 whitespace-nowrap w-[300px] hidden md:flex">
                    <StyledDatePicker value={selectedDate} onChange={(date) => setSelectedDate(date ?? new Date())} />
                </div>
            } rightContent={
                <HeaderTextButton colorVariant={COLOR_VARIANT.SECONDARY}
                    onClick={() => { /* TODO: 保存処理 */ }}>
                    <Save size={20} strokeWidth={2} />
                    保存
                </HeaderTextButton>}
                actionButtons={actionButtons}
            />
            <main className="p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto">
                <PlanEditCard />
            </main>
        </>
    );
};

export default PlanEditPage;