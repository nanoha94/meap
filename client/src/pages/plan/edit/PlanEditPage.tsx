"use client";
import { Header, HeaderTextButton } from "@/components/common";
import { COLOR_VARIANT } from "@/constants";
import { ActionButton } from "@/types";
import { Save, Trash } from "lucide-react";


const PlanEditPage = () => {

    /**
         * メニューボタン押下時に開くアクションボタン設定
         */
    const actionButtons: ActionButton[] = [
        {
            label: '削除する',
            icon: <Trash size={20} strokeWidth={2} />,
            onClick: () => {
                // TODO: 削除ダイアログ実装
            },
            color: COLOR_VARIANT.ALERT,
        },
    ];
    return (
        <>
            <Header hasBackButton={true} rightContent={
                <HeaderTextButton colorVariant={COLOR_VARIANT.SECONDARY}
                    onClick={() => { console.log('save'); }}>
                    <Save size={20} strokeWidth={2} />
                    保存
                </HeaderTextButton>}
                actionButtons={actionButtons}
            />
            <main className="p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto">
                aaa
            </main>
        </>
    );
};

export default PlanEditPage;